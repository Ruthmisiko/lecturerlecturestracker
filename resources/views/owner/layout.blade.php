<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'System Owner') · Lecturer Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #2d2a6b; --orange: #f26a2e; --bg: #f4f5f9; }
        body { background: var(--bg); color: #1f2333; }
        .owner-nav { background: var(--navy); }
        .owner-nav .brand { color: #fff; font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 10px; }
        .owner-nav .brand .mark { width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 3px solid var(--orange);
            display: inline-flex; align-items: center; justify-content: center; color: var(--navy); font-weight: 800; }
        .owner-nav .nav-link { color: rgba(255,255,255,.8); border-radius: 8px; padding: 6px 14px; }
        .owner-nav .nav-link:hover { color: #fff; background: rgba(255,255,255,.1); }
        .owner-nav .nav-link.active { color: #fff; background: var(--orange); }
        .card { border: 0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
        .stat .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat .label { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin: 0; }
        .stat .value { font-size: 28px; font-weight: 700; margin: 0; line-height: 1.1; }
        .btn-orange { background: var(--orange); border-color: var(--orange); color: #fff; }
        .btn-orange:hover { background: #d9561c; border-color: #d9561c; color: #fff; }
        .btn-navy { background: var(--navy); border-color: var(--navy); color: #fff; }
        .btn-navy:hover { background: #211f52; color: #fff; }
        .table thead th { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; border-top: 0; }
        .badge-active { background: #e6f6ec; color: #18794e; }
        .badge-inactive { background: #fdecea; color: #b42318; }
        a { color: var(--navy); }
    </style>
</head>
<body>
<nav class="owner-nav px-3 py-2 mb-4">
    <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
        <div class="brand"><span class="mark">N</span> Nawema · System Owner</div>
        <div class="d-flex flex-wrap align-items-center" style="gap:4px;">
            <a href="{{ route('owner.dashboard') }}" class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}"><i class="fas fa-chart-pie mr-1"></i> Dashboard</a>
            <a href="{{ route('owner.institutions') }}" class="nav-link {{ request()->routeIs('owner.institutions') ? 'active' : '' }}"><i class="fas fa-university mr-1"></i> Institutions</a>
            <a href="{{ route('owner.users') }}" class="nav-link {{ request()->routeIs('owner.users') ? 'active' : '' }}"><i class="fas fa-users mr-1"></i> Users</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button class="btn btn-sm btn-outline-light"><i class="fas fa-sign-out-alt mr-1"></i> Logout</button>
            </form>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 px-md-4 pb-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @yield('content')
</main>

<footer class="text-center text-muted small pb-4">Powered by <a href="https://nawema.co.ke" target="_blank" rel="noopener" class="font-weight-bold">nawema.co.ke</a></footer>
</body>
</html>
