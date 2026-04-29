@extends('layouts.app')

@section('content')

<div class="mb-3">
    <h3 class="mb-1">Edit Message Template</h3>
    <p class="text-muted mb-0">Update the template content and keep it ready for new campaigns.</p>
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

<form method="POST" action="{{ route('message-templates.update', $template->id) }}">
    @csrf
    @method('PUT')
    @include('message-templates.form')
</form>

@endsection
