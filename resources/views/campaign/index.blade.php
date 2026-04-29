@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Campaign List</h3>
        <p class="text-muted mb-0">Manage the 3-step message campaigns and run deliveries from here.</p>
    </div>
    <a href="/campaigns/create" class="btn btn-primary">+ Create Campaign</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Channel</th>
            <th>Customers</th>
            <th>Start</th>
            <th>Status</th>
            <th>Logs</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        @foreach($campaigns as $campaign)
        <tr>
            <td>{{ $campaign->id }}</td>
            <td>{{ $campaign->name }}</td>
            <td>{{ strtoupper($campaign->type ?? '-') }}</td>
            <td>{{ count($campaign->config['step1']['customer_ids'] ?? []) }}</td>
            <td>{{ $campaign->start_date }}</td>

            <td>
                @if($campaign->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-danger">Draft</span>
                @endif
            </td>
            <td>{{ $campaign->communication_logs_count }}</td>

            <td>
                <form method="POST" action="{{ route('campaigns.run', $campaign->id) }}" style="display:inline;">
                    @csrf
                    <button class="btn btn-sm btn-success">
                        Run
                    </button>
                </form>
                <form method="POST" action="{{ route('campaign.toggle', $campaign->id) }}" style="display:inline;">
                    @csrf
                    <button class="btn btn-sm btn-warning">
                        {{ $campaign->is_active ? 'Mark Draft' : 'Mark Active' }}
                    </button>
                </form>
                <a href="{{ route('campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-info">Edit</a>
                <form method="POST" action="{{ route('campaigns.destroy', $campaign->id) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>

            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $campaigns->links() }}

@endsection
