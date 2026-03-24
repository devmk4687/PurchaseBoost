@extends('layouts.app')

@section('content')

<h3>Create Campaign</h3>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('campaigns.store') }}">
    @csrf

    <div class="mb-3">
        <label>Campaign Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <div class="mb-3">
        <label>Start Date</label>
        <input type="date" name="start_date" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>End Date</label>
        <input type="date" name="end_date" class="form-control">
    </div>

    <hr>

    <h5>Configuration</h5>

    <div class="mb-3">
        <label>Discount (%)</label>
        <input type="number" name="config[discount]" class="form-control">
    </div>

    <div class="mb-3">
        <label>Minimum Order Value</label>
        <input type="number" name="config[min_order]" class="form-control">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="is_active" class="form-check-input" checked>
        <label class="form-check-label">Active</label>
    </div>

    <button class="btn btn-success">Save Campaign</button>
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Back</a>

</form>

@endsection