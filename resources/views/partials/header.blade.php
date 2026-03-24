<nav class="navbar navbar-light bg-light mb-3">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Dashboard</span>
         @auth
        <div class="d-flex align-items-center">

            <img src="{{ auth()->user()->avatar ?? 'https://via.placeholder.com/40' }}"
                 width="40" height="40" 
                 class="rounded-circle me-2" alt="AdminUserIMG">

            

        </div>
        @endauth
        @auth
        <span>Welcome, {{ auth()->user()->name }}</span>
        @endauth

        @guest
            <a href="/login">Login</a>
        @endguest
    </div>
</nav>