<h4 class="p-3">Admin</h4>

<a href="/dashboard">Dashboard</a>

<div class="px-3 pt-3 text-uppercase small text-white-50">Campaigns</div>
<a href="{{ route('campaigns.index') }}">Campaign List</a>
<a href="{{ route('campaigns.create') }}">Create Campaign</a>
<a href="{{ route('campaign-agent.index') }}">Campaign Agent</a>

<div class="px-3 pt-3 text-uppercase small text-white-50">Templates</div>
<a href="{{ route('message-templates.index') }}">Message Templates</a>
<a href="{{ route('message-templates.create') }}">Create Template</a>

<div class="px-3 pt-3 text-uppercase small text-white-50">Customers</div>
<a href="{{ route('loyalty-members.index') }}">Loyalty Members</a>
<a href="{{ route('loyalty-members.create') }}">Create Member</a>
<a href="{{ route('customer-segments.index') }}">Customer Segments</a>

<div class="px-3 pt-3 text-uppercase small text-white-50">Orders</div>
<a href="{{ route('orders.import.index') }}">Import Orders</a>

<hr>

<form method="POST" action="/logout">
    @csrf
    <button class="btn btn-danger w-100">Logout</button>
</form>
