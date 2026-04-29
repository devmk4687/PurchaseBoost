@extends('layouts.app')

@section('content')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #0f766e 0%, #155e75 55%, #1d4ed8 100%);
        border-radius: 24px;
        color: #fff;
        overflow: hidden;
        position: relative;
    }

    .dashboard-hero::after {
        content: "";
        position: absolute;
        inset: auto -60px -80px auto;
        width: 260px;
        height: 260px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .metric-card,
    .panel-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .metric-label {
        color: #64748b;
        font-size: 0.95rem;
    }

    .metric-accent {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .mini-stat {
        border-radius: 16px;
        background: #f8fafc;
    }

    .table-soft th {
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .chart-wrap {
        position: relative;
        height: 320px;
    }
</style>

<div class="dashboard-hero p-4 p-lg-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <p class="text-uppercase small mb-2" style="letter-spacing: 0.12em; opacity: 0.8;">Campaign Command Center</p>
            <h2 class="mb-2">Track campaign creation, delivery success, and the latest execution activity.</h2>
            <p class="mb-0" style="opacity: 0.9;">This dashboard is now driven by live database metrics from campaigns and communication logs.</p>
        </div>
        <div class="col-lg-4">
            <div class="row g-3">
                <div class="col-6">
                    <div class="mini-stat p-3 text-dark">
                        <div class="small text-muted">Created This Month</div>
                        <div class="fs-3 fw-bold">{{ $campaignsCreatedThisMonth }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="mini-stat p-3 text-dark">
                        <div class="small text-muted">Success Rate</div>
                        <div class="fs-3 fw-bold">{{ $successRate }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card metric-card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label mb-2">Total Campaigns</div>
                    <div class="metric-value">{{ $totalCampaigns }}</div>
                </div>
                <div class="metric-accent text-primary" style="background: #dbeafe;">C</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card metric-card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label mb-2">Published Campaigns</div>
                    <div class="metric-value">{{ $publishedCampaigns }}</div>
                </div>
                <div class="metric-accent text-success" style="background: #dcfce7;">P</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card metric-card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label mb-2">Draft Campaigns</div>
                    <div class="metric-value">{{ $draftCampaigns }}</div>
                </div>
                <div class="metric-accent text-warning" style="background: #fef3c7;">D</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card metric-card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label mb-2">Successful Deliveries</div>
                    <div class="metric-value">{{ $successfulDeliveries }}</div>
                </div>
                <div class="metric-accent text-info" style="background: #cffafe;">S</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card panel-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Campaign Adoption Rate</h5>
                        <p class="text-muted mb-0">Success trend for delivery activity over the last 14 days.</p>
                    </div>
                    <div class="mini-stat px-3 py-2">
                        <div class="small text-muted">Current overall success</div>
                        <div class="fw-bold fs-5">{{ $successRate }}%</div>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="adoptionRateChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card panel-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Recent Campaigns</h5>
                        <p class="text-muted mb-0">Latest campaigns created in the system.</p>
                    </div>
                </div>

                @if ($recentCampaigns->isEmpty())
                    <p class="text-muted mb-0">No campaigns created yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-soft align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Logs</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentCampaigns as $campaign)
                                    <tr>
                                        <td class="fw-semibold">{{ $campaign->name }}</td>
                                        <td>{{ strtoupper($campaign->type ?? '-') }}</td>
                                        <td>
                                            <span class="badge {{ $campaign->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ ucfirst($campaign->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $campaign->communication_logs_count }}</td>
                                        <td>{{ $campaign->created_at?->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card panel-card h-100">
            <div class="card-body">
                <h5 class="mb-1">Delivery Snapshot</h5>
                <p class="text-muted mb-4">Live performance from communication logs.</p>

                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="mini-stat p-3 h-100">
                            <div class="small text-muted">Total</div>
                            <div class="fs-4 fw-bold">{{ $totalDeliveries }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mini-stat p-3 h-100">
                            <div class="small text-muted">Sent</div>
                            <div class="fs-4 fw-bold text-success">{{ $successfulDeliveries }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mini-stat p-3 h-100">
                            <div class="small text-muted">Failed</div>
                            <div class="fs-4 fw-bold text-danger">{{ $failedDeliveries }}</div>
                        </div>
                    </div>
                </div>

                @if ($channelPerformance->isEmpty())
                    <p class="text-muted mb-0">No delivery activity available yet.</p>
                @else
                    @foreach ($channelPerformance as $channel)
                        @php
                            $channelRate = $channel->total > 0 ? round(($channel->sent / $channel->total) * 100) : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">{{ strtoupper($channel->channel) }}</span>
                                <span class="text-muted">{{ $channel->sent }}/{{ $channel->total }} sent</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $channelRate }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card panel-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Latest Delivery Logs</h5>
                <p class="text-muted mb-0">Recent communication attempts across campaigns.</p>
            </div>
        </div>

        @if ($recentDeliveries->isEmpty())
            <p class="text-muted mb-0">No delivery logs found yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-soft align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Recipient</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentDeliveries as $log)
                            <tr>
                                <td>{{ optional($log->campaign)->name ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $log->recipient }}</div>
                                    <div class="small text-muted">
                                        {{ optional($log->customer)->firstName }} {{ optional($log->customer)->lastName }}
                                    </div>
                                </td>
                                <td>{{ strtoupper($log->channel) }}</td>
                                <td>
                                    <span class="badge {{ $log->status === 'sent' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($log->sent_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartElement = document.getElementById('adoptionRateChart');

        if (!chartElement) {
            return;
        }

        var adoptionTrend = @json($adoptionTrend);

        new Chart(chartElement, {
            type: 'line',
            data: {
                labels: adoptionTrend.map(function (item) { return item.label; }),
                datasets: [{
                    label: 'Adoption Rate (%)',
                    data: adoptionTrend.map(function (item) { return item.rate; }),
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15, 118, 110, 0.16)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#0f766e'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var item = adoptionTrend[context.dataIndex];
                                return 'Rate: ' + item.rate + '% | Sent: ' + item.sent + ' | Total: ' + item.total;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
