@extends('layouts.app')

@section('content')

<h3>Edit Campaign</h3>

<form method="POST" action="{{ route('campaigns.update', $campaign->id) }}">
    @csrf
    @method('PUT')

    <input type="text" name="name" value="{{ $campaign->name }}" class="form-control mb-2">
    <input type="text" name="type" value="{{ $campaign->type }}" class="form-control mb-2">

    <input type="date" name="start_date" value="{{ $campaign->start_date }}" class="form-control mb-2">
    <input type="date" name="end_date" value="{{ $campaign->end_date }}" class="form-control mb-2">
    <textarea name="config"  class="form-control mb-2">{{ json_encode($campaign->config) }}</textarea>

    <button class="btn btn-primary">Update</button>

</form>

@endsection
