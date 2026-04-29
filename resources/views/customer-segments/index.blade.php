@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1">Customer Segments</h3>
        <p class="text-muted mb-0">Build customer segments using current tier, average order value, and order frequency.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total Loyalty Members</div>
                <div class="fs-3 fw-bold">{{ $summary['customers'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Matched Segment</div>
                <div class="fs-3 fw-bold">{{ $summary['segmented_customers'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Silver</div>
                <div class="fs-3 fw-bold text-secondary">{{ $summary['silver'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Gold</div>
                <div class="fs-3 fw-bold text-warning">{{ $summary['gold'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Platinum</div>
                <div class="fs-3 fw-bold text-info">{{ $summary['platinum'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Segment Filters</h5>
        <form method="GET" action="{{ route('customer-segments.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tier</label>
                <select name="tier" class="form-select">
                    <option value="">All Tiers</option>
                    @foreach (['Basic', 'Silver', 'Gold', 'Platinum', 'No Tier'] as $tier)
                        <option value="{{ $tier }}" @selected(($filters['tier'] ?? '') === $tier)>{{ $tier }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Order Frequency</label>
                <select name="frequency" class="form-select">
                    <option value="">All Frequency Bands</option>
                    <option value="inactive" @selected(($filters['frequency'] ?? '') === 'inactive')>Inactive (0 Orders)</option>
                    <option value="low" @selected(($filters['frequency'] ?? '') === 'low')>Low (1-2 Orders)</option>
                    <option value="medium" @selected(($filters['frequency'] ?? '') === 'medium')>Medium (3-5 Orders)</option>
                    <option value="high" @selected(($filters['frequency'] ?? '') === 'high')>High (6+ Orders)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Min Avg Price</label>
                <input type="number" step="0.01" name="min_avg_order_value" value="{{ $filters['min_avg_order_value'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Max Avg Price</label>
                <input type="number" step="0.01" name="max_avg_order_value" value="{{ $filters['max_avg_order_value'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                    <a href="{{ route('customer-segments.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($segments->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Save Segment Master</h5>
            <form method="POST" action="{{ route('customer-segments.store') }}" class="row g-3 align-items-end">
                @csrf
                <input type="hidden" name="tier" value="{{ $filters['tier'] ?? '' }}">
                <input type="hidden" name="frequency" value="{{ $filters['frequency'] ?? '' }}">
                <input type="hidden" name="min_avg_order_value" value="{{ $filters['min_avg_order_value'] ?? '' }}">
                <input type="hidden" name="max_avg_order_value" value="{{ $filters['max_avg_order_value'] ?? '' }}">

                <div class="col-md-4">
                    <label class="form-label">Segment Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional note about this segment">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Save Segment</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Segment Results</h5>

        @if ($segments->isEmpty())
            <p class="text-muted mb-0">No customers match the selected segment filters.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Tier</th>
                            <th>Total Orders</th>
                            <th>Avg Order Value</th>
                            <th>Total Spend</th>
                            <th>Frequency</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($segments as $segment)
                            @php
                                $frequency = $segment->total_orders == 0 ? 'Inactive' : ($segment->total_orders <= 2 ? 'Low' : ($segment->total_orders <= 5 ? 'Medium' : 'High'));
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $segment->customerId }} - {{ $segment->firstName }} {{ $segment->lastName }}</div>
                                    <div class="small text-muted">{{ $segment->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $segment->tier_segment }}</span>
                                </td>
                                <td>{{ $segment->total_orders }}</td>
                                <td>{{ number_format((float) $segment->average_order_value, 2) }}</td>
                                <td>{{ number_format((float) $segment->total_spend, 2) }}</td>
                                <td>{{ $frequency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                <p class="text-muted mb-0">
                    Showing {{ $segments->firstItem() ?? 0 }} to {{ $segments->lastItem() ?? 0 }} of {{ $segments->total() }} customers
                </p>
                <div>
                    {{ $segments->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Saved Segment Masters</h5>

        @if ($savedSegments->isEmpty())
            <p class="text-muted mb-0">No saved segment masters yet.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Members</th>
                            <th>Filters</th>
                            <th>Use</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($savedSegments as $savedSegment)
                            <tr>
                                <td class="fw-semibold">{{ $savedSegment->name }}</td>
                                <td>{{ $savedSegment->description ?: '-' }}</td>
                                <td>{{ $savedSegment->members_count }}</td>
                                <td class="small text-muted">
                                    Tier: {{ $savedSegment->filters['tier'] ?: 'Any' }},
                                    Frequency: {{ $savedSegment->filters['frequency'] ?: 'Any' }}
                                </td>
                                <td>
                                    <a href="{{ route('campaign-agent.index', ['segment_id' => $savedSegment->id]) }}" class="btn btn-sm btn-outline-primary">
                                        Use in Campaign Agent
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                <p class="text-muted mb-0">
                    Showing {{ $savedSegments->firstItem() ?? 0 }} to {{ $savedSegments->lastItem() ?? 0 }} of {{ $savedSegments->total() }} saved segments
                </p>
                <div>
                    {{ $savedSegments->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
