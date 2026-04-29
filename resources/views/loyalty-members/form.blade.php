<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Customer ID</label>
        <input type="text" name="customerId" value="{{ old('customerId', $member->customerId ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $member->email ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">First Name</label>
        <input type="text" name="firstName" value="{{ old('firstName', $member->firstName ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Last Name</label>
        <input type="text" name="lastName" value="{{ old('lastName', $member->lastName ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Company</label>
        <input type="text" name="company" value="{{ old('company', $member->company ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="text" name="website" value="{{ old('website', $member->website ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{{ old('city', $member->city ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country" value="{{ old('country', $member->country ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Subscription Date</label>
        <input type="date" name="subscriptionDate" value="{{ old('subscriptionDate', $member->subscriptionDate ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Primary Phone</label>
        <input type="text" name="phone1" value="{{ old('phone1', $member->phone1 ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Secondary Phone</label>
        <input type="text" name="phone2" value="{{ old('phone2', $member->phone2 ?? '') }}" class="form-control">
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('loyalty-members.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
