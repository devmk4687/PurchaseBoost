@extends('layouts.app')

@section('content')

<h3>Campaign List</h3>

<a href="/campaigns/create" class="btn btn-primary mb-3">+ Create Campaign</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Start</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        @foreach($campaigns as $campaign)
        <tr>
            <td>{{ $campaign->id }}</td>
            <td>{{ $campaign->name }}</td>
            <td>{{ $campaign->start_date }}</td>

            <td>
                @if($campaign->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-danger">Inactive</span>
                @endif
            </td>

            <td>

                <!-- Toggle -->
                <form method="POST" action="{{ route('campaign.toggle', $campaign->id) }}" style="display:inline;">
                    @csrf
                    <button class="btn btn-sm btn-warning">
                        Toggle
                    </button>
                </form>

                <!-- Edit -->
                <a href="{{ route('campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-info">Edit</a>

                <!-- Delete -->
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
