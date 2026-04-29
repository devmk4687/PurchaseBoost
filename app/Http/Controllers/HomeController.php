<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CommunicationLog;
use App\Models\LoyaltyMember;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $totalCampaigns = Campaign::count();
        $publishedCampaigns = Campaign::where('status', Campaign::STATUS_PUBLISHED)->count();
        $draftCampaigns = Campaign::where('status', Campaign::STATUS_DRAFT)->count();
        $campaignsCreatedThisMonth = Campaign::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalDeliveries = CommunicationLog::count();
        $successfulDeliveries = CommunicationLog::where('status', 'sent')->count();
        $failedDeliveries = CommunicationLog::where('status', 'failed')->count();
        $successRate = $totalDeliveries > 0
            ? round(($successfulDeliveries / $totalDeliveries) * 100, 1)
            : 0;

        $recentCampaigns = Campaign::withCount('communicationLogs')
            ->latest()
            ->take(5)
            ->get();

        $channelPerformance = CommunicationLog::selectRaw('channel, count(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('channel')
            ->orderBy('channel')
            ->get();

        $recentDeliveries = CommunicationLog::with(['campaign:id,name', 'customer:id,firstName,lastName,email'])
            ->latest()
            ->take(8)
            ->get();

        $adoptionTrend = collect(range(13, 0))
            ->map(function ($daysAgo) {
                $date = now()->copy()->subDays($daysAgo);
                $total = CommunicationLog::whereDate('created_at', $date)->count();
                $sent = CommunicationLog::whereDate('created_at', $date)
                    ->where('status', 'sent')
                    ->count();

                return [
                    'label' => $date->format('d M'),
                    'rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
                    'sent' => $sent,
                    'total' => $total,
                ];
            })
            ->values();

        return view('dashboard', compact(
            'totalCampaigns',
            'publishedCampaigns',
            'draftCampaigns',
            'campaignsCreatedThisMonth',
            'totalDeliveries',
            'successfulDeliveries',
            'failedDeliveries',
            'successRate',
            'recentCampaigns',
            'channelPerformance',
            'recentDeliveries',
            'adoptionTrend'
        ));
    }
}
