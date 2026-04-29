@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Message Templates</h3>
        <p class="text-muted mb-0">Reusable Email, SMS, and WhatsApp content for campaign step 2.</p>
    </div>
    <a href="{{ route('message-templates.create') }}" class="btn btn-primary">+ Create Template</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('message-templates.index') }}" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Filter By Channel</label>
                <select name="channel" class="form-select" onchange="this.form.submit()">
                    <option value="">All Channels</option>
                    <option value="email" @selected(request('channel') === 'email')>Email</option>
                    <option value="sms" @selected(request('channel') === 'sms')>SMS</option>
                    <option value="whatsapp" @selected(request('channel') === 'whatsapp')>WhatsApp</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Channel</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ strtoupper($template->channel) }}</td>
                            <td>{{ $template->subject ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('message-templates.edit', $template->id) }}" class="btn btn-sm btn-info">Edit</a>
                                <form method="POST" action="{{ route('message-templates.destroy', $template->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $templates->links() }}
    </div>
</div>

@endsection
