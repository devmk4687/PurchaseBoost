@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1">Order Import</h3>
        <p class="text-muted mb-0">Bulk import orders from CSV and apply the same points and tier logic used by the API.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Upload CSV File</h5>
                <form method="POST" action="{{ route('orders.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                    </div>

                    <div class="small text-muted mb-3">
                        Expected columns:
                        <code>customerId, orderId, price, orderStatus, orderDate, description</code>
                    </div>

                    <div class="small text-muted mb-3">
                        The file should contain the client's original <code>customerId</code>. During import, the system matches that value with loyalty members and saves the matched internal DB ID into both orders and transactions.
                    </div>

                    <button type="submit" class="btn btn-primary">Import Orders</button>
                </form>
            </div>
        </div>

        @if ($importSummary)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="mb-3">Latest Import Summary</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="small text-muted">Processed</div>
                                <div class="fs-4 fw-semibold">{{ $importSummary['processed'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 bg-success-subtle">
                                <div class="small text-muted">Created</div>
                                <div class="fs-4 fw-semibold">{{ $importSummary['created'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 bg-warning-subtle">
                                <div class="small text-muted">Duplicates</div>
                                <div class="fs-4 fw-semibold">{{ $importSummary['duplicates'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 bg-danger-subtle">
                                <div class="small text-muted">Failed</div>
                                <div class="fs-4 fw-semibold">{{ $importSummary['failed'] }}</div>
                            </div>
                        </div>
                    </div>

                    @if (!empty($importSummary['errors']))
                        <h6 class="mb-2">Row Errors</h6>
                        <ul class="mb-0 small text-danger">
                            @foreach ($importSummary['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Recent Orders</h5>

                @if ($recentOrders->isEmpty())
                    <p class="text-muted mb-0">No orders imported yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Tier</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td>{{ $order->orderId }}</td>
                                        <td>{{ $order->custId }}</td>
                                        <td>{{ number_format((float) $order->price, 2) }}</td>
                                        <td>{{ $order->orderStatus }}</td>
                                        <td>{{ $order->tierStatus }}</td>
                                        <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
