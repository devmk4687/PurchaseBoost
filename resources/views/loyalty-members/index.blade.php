@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1">Loyalty Members</h3>
        <p class="text-muted mb-0">Manage member records and import them in bulk.</p>
    </div>
    <a href="{{ route('loyalty-members.create') }}" class="btn btn-primary">+ Add Member</a>
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

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h5 class="mb-1">CSV Bulk Upload</h5>
                <p class="text-muted mb-0">Accepted columns: `customerId, firstName, lastName, company, city, country, phone1, phone2, email, subscriptionDate, website`.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('loyalty-members.import') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-8">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-dark w-100">Upload Members</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <form method="GET" action="{{ route('loyalty-members.index') }}">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Company</th>
                        <th>City</th>
                        <th>Country</th>
                        <th>Phone 1</th>
                        <th>Phone 2</th>
                        <th>Email</th>
                        <th>Subscription</th>
                        <th>Website</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    <tr>
                        <th><input type="text" name="customerId" value="{{ $filters['customerId'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="firstName" value="{{ $filters['firstName'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="lastName" value="{{ $filters['lastName'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="company" value="{{ $filters['company'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="city" value="{{ $filters['city'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="country" value="{{ $filters['country'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="phone1" value="{{ $filters['phone1'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="phone2" value="{{ $filters['phone2'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="email" value="{{ $filters['email'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th><input type="text" name="subscriptionDate" value="{{ $filters['subscriptionDate'] ?? '' }}" class="form-control form-control-sm" placeholder="YYYY-MM-DD"></th>
                        <th><input type="text" name="website" value="{{ $filters['website'] ?? '' }}" class="form-control form-control-sm" placeholder="Filter"></th>
                        <th class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                <a href="{{ route('loyalty-members.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $member->customerId }}</td>
                            <td>{{ $member->firstName }}</td>
                            <td>{{ $member->lastName }}</td>
                            <td>{{ $member->company }}</td>
                            <td>{{ $member->city }}</td>
                            <td>{{ $member->country }}</td>
                            <td>{{ $member->phone1 }}</td>
                            <td>{{ $member->phone2 }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->subscriptionDate }}</td>
                            <td>
                                @if ($member->website)
                                    <a href="{{ $member->website }}" target="_blank" rel="noopener noreferrer">{{ $member->website }}</a>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('loyalty-members.edit', $member->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('loyalty-members.destroy', $member->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this member?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No loyalty members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </form>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
    <p class="text-muted mb-0">
        Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of {{ $members->total() }} members
    </p>
    <div>
        {{ $members->onEachSide(1)->links() }}
    </div>
</div>
@endsection
