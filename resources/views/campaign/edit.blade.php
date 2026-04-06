@extends('layouts.app')

@section('content')

<h3>Edit Campaign</h3>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('campaigns.update', $campaign->id) }}">
    @csrf
    @method('PUT')

    <input type="text" name="name" value="{{ $campaign->name }}" class="form-control mb-2">
    <input type="text" name="type" value="{{ $campaign->type }}" class="form-control mb-2">

    <input type="date" name="start_date" value="{{ $campaign->start_date }}" class="form-control mb-2">
    <input type="date" name="end_date" value="{{ $campaign->end_date }}" class="form-control mb-2">
    <textarea name="config"  class="form-control mb-2">{{ json_encode($campaign->config) }}</textarea>

    <div class="form-check mb-3">
        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ $campaign->is_active ? 'checked' : '' }}>
        <label class="form-check-label">Active</label>
    </div>

    <button class="btn btn-primary">Update</button>

</form>

@endsection
