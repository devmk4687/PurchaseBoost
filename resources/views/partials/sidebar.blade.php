<h4 class="p-3">Admin</h4>

<a href="/dashboard">Dashboard</a>
<a href="/campaigns/create">Create Campaign</a>
<a href="/campaigns/">Campaign List</a>
<a href="#">Users</a>

<hr>

<form method="POST" action="/logout">
    @csrf
    <button class="btn btn-danger w-100">Logout</button>
</form>