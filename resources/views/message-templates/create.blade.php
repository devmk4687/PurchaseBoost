@extends('layouts.app')

@section('content')

<div class="mb-3">
    <h3 class="mb-1">Create Message Template</h3>
    <p class="text-muted mb-0">Create reusable templates for Email, SMS, or WhatsApp campaigns.</p>
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

<form method="POST" action="{{ route('message-templates.store') }}">
    @csrf
    @include('message-templates.form')
</form>

@endsection
