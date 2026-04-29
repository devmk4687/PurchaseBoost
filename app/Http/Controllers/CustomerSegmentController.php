<?php

namespace App\Http\Controllers;

use App\Models\CustomerSegment;
use App\Models\LoyaltyMember;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerSegmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'tier' => $request->string('tier')->toString(),
            'frequency' => $request->string('frequency')->toString(),
            'min_avg_order_value' => $request->string('min_avg_order_value')->toString(),
            'max_avg_order_value' => $request->string('max_avg_order_value')->toString(),
        ];

        $segments = $this->segmentQuery($filters)
            ->orderByDesc('total_spend')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'customers' => LoyaltyMember::count(),
            'segmented_customers' => $segments->total(),
            'basic' => Order::where('tierStatus', 'Basic')->distinct('custId')->count('custId'),
            'silver' => Order::where('tierStatus', 'Silver')->distinct('custId')->count('custId'),
            'gold' => Order::where('tierStatus', 'Gold')->distinct('custId')->count('custId'),
            'platinum' => Order::where('tierStatus', 'Platinum')->distinct('custId')->count('custId'),
        ];

        $savedSegments = CustomerSegment::withCount('members')
            ->latest()
            ->paginate(10, ['*'], 'master_page');

        return view('customer-segments.index', compact('segments', 'filters', 'summary', 'savedSegments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tier' => ['nullable', 'string'],
            'frequency' => ['nullable', 'string'],
            'min_avg_order_value' => ['nullable', 'numeric'],
            'max_avg_order_value' => ['nullable', 'numeric'],
        ]);

        $filters = [
            'tier' => (string) ($validated['tier'] ?? ''),
            'frequency' => (string) ($validated['frequency'] ?? ''),
            'min_avg_order_value' => (string) ($validated['min_avg_order_value'] ?? ''),
            'max_avg_order_value' => (string) ($validated['max_avg_order_value'] ?? ''),
        ];

        $memberIds = $this->segmentQuery($filters)->pluck('customer_details.id')->all();

        if ($memberIds === []) {
            return redirect()
                ->route('customer-segments.index', $filters)
                ->withErrors(['name' => 'This segment has no matching customers, so it cannot be saved.']);
        }

        $segment = CustomerSegment::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'filters' => $filters,
        ]);

        $segment->members()->sync($memberIds);

        return redirect()
            ->route('customer-segments.index', $filters)
            ->with('success', 'Segment master saved successfully.');
    }

    private function segmentQuery(array $filters)
    {
        $latestOrderSubquery = Order::query()
            ->select('custId', DB::raw('MAX(created_at) as latest_order_at'))
            ->groupBy('custId');

        return LoyaltyMember::query()
            ->leftJoin('orders', 'customer_details.id', '=', 'orders.custId')
            ->leftJoinSub($latestOrderSubquery, 'latest_order_meta', function ($join) {
                $join->on('latest_order_meta.custId', '=', 'customer_details.id');
            })
            ->leftJoin('orders as latest_order', function ($join) {
                $join->on('latest_order.custId', '=', 'customer_details.id')
                    ->on('latest_order.created_at', '=', 'latest_order_meta.latest_order_at');
            })
            ->select([
                'customer_details.id',
                'customer_details.customerId',
                'customer_details.firstName',
                'customer_details.lastName',
                'customer_details.email',
                'customer_details.company',
                'customer_details.city',
                'customer_details.country',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(orders.price), 0) as total_spend'),
                DB::raw('COALESCE(AVG(orders.price), 0) as average_order_value'),
                DB::raw("COALESCE(latest_order.tierStatus, 'No Tier') as tier_segment"),
            ])
            ->groupBy([
                'customer_details.id',
                'customer_details.customerId',
                'customer_details.firstName',
                'customer_details.lastName',
                'customer_details.email',
                'customer_details.company',
                'customer_details.city',
                'customer_details.country',
                'latest_order.tierStatus',
            ])
            ->when($filters['tier'] !== '', function ($query) use ($filters) {
                $query->having('tier_segment', '=', $filters['tier']);
            })
            ->when($filters['min_avg_order_value'] !== '', function ($query) use ($filters) {
                $query->havingRaw('COALESCE(AVG(orders.price), 0) >= ?', [(float) $filters['min_avg_order_value']]);
            })
            ->when($filters['max_avg_order_value'] !== '', function ($query) use ($filters) {
                $query->havingRaw('COALESCE(AVG(orders.price), 0) <= ?', [(float) $filters['max_avg_order_value']]);
            })
            ->when($filters['frequency'] !== '', function ($query) use ($filters) {
                match ($filters['frequency']) {
                    'low' => $query->havingRaw('COUNT(orders.id) BETWEEN 1 AND 2'),
                    'medium' => $query->havingRaw('COUNT(orders.id) BETWEEN 3 AND 5'),
                    'high' => $query->havingRaw('COUNT(orders.id) >= 6'),
                    'inactive' => $query->havingRaw('COUNT(orders.id) = 0'),
                    default => null,
                };
            });
    }
}
