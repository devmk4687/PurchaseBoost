@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">Edit Loyalty Member</h3>
        <p class="text-muted mb-0">Update loyalty member details.</p>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('loyalty-members.update', $member->id) }}">
            @csrf
            @method('PUT')
            @php($submitLabel = 'Update Member')
            @include('loyalty-members.form')
        </form>
    </div>
</div>
@endsection
