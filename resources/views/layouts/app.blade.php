<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Lecturer Tracker')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css') }}">
    <!-- Add to your head section if not present -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .nav-link {
        transition: background-color 0.3s ease;
    }

    .nav-link:hover {
        background-color: #218838 !important; /* Darker green on hover */
    }
    .nav-link.active {
        background-color: #1e7e34 !important;
    }
</style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

 <!-- Navbar -->
 <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Hamburger Button -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right: Department Switcher -->
        @auth
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                @php
                    $navUser     = Auth::user();
                    $navOwnerId  = $navUser->user_id ?? $navUser->id;
                    $navDepts    = $navUser->hasRole('superuser')
                        ? \App\Models\Department::orderBy('name')->get()
                        : \App\Models\Department::where('user_id', $navOwnerId)->orderBy('name')->get();
                    $navActiveDeptId = session()->has('active_department_id')
                        ? session('active_department_id')
                        : ($navUser->department_id ?? null);
                    $navActiveDept = $navActiveDeptId ? $navDepts->firstWhere('id', $navActiveDeptId) : null;
                @endphp
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-building mr-1"></i>
                    {{ $navActiveDept ? $navActiveDept->name : 'All Departments' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <form method="POST" action="{{ route('department.switch') }}" id="deptSwitchForm">
                        @csrf
                        <input type="hidden" name="department_id" id="deptSwitchInput">
                        <button type="button" class="dropdown-item {{ !$navActiveDeptId ? 'active font-weight-bold' : '' }}"
                                onclick="switchDept('')">
                            All Departments
                        </button>
                        @if($navDepts->isNotEmpty())
                            <div class="dropdown-divider"></div>
                            @foreach($navDepts as $navDept)
                                <button type="button"
                                        class="dropdown-item {{ $navActiveDeptId == $navDept->id ? 'active font-weight-bold' : '' }}"
                                        onclick="switchDept({{ $navDept->id }})">
                                    {{ $navDept->name }}
                                </button>
                            @endforeach
                        @endif
                    </form>
                </div>
            </li>
        </ul>
        @endauth
    </nav>
    <!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-success bg-success elevation-4">
    <a href="{{ url('/') }}" class="brand-link">
        <span class="brand-text font-weight-bold text-white">Lecturer Tracker</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            @include('layouts.menu') {{-- use your custom menu --}}
        </nav>
    </div>
</aside>


    <!-- Main content -->
    <div class="content-wrapper">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="main-footer text-center">
        <strong>&copy; {{ date('Y') }} Lecturer Tracker System</strong>
    </footer>
</div>

<!-- Scripts -->
<script src="{{ asset('https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js') }}"></script>
<script src="{{ asset('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js') }}"></script>

@stack('scripts')
<script>
function switchDept(id) {
    document.getElementById('deptSwitchInput').value = id;
    document.getElementById('deptSwitchForm').submit();
}
</script>
</body>
</html>
