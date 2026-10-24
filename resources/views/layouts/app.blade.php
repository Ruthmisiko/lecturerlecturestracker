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

@php $theme = \App\Support\Theme::current(); @endphp
<style>
    /* Institution theme (Institutions → Settings) */
    :root {
        --primary: {{ $theme->primary }};
        --primary-dark: {{ $theme->primaryDark() }};
        --primary-soft: {{ $theme->primarySoft() }};
        --primary-rgb: {{ $theme->primaryRgb() }};
        --primary-text: {{ $theme->primaryText() }};
        --secondary: {{ $theme->secondary }};
        --secondary-soft: {{ $theme->secondarySoft() }};
        --secondary-text: {{ $theme->secondaryText() }};
    }

    /* ---- Sidebar ---- */
    .main-sidebar.themed-sidebar {
        background: var(--primary) !important;
        border-radius: 0 18px 18px 0;
        overflow: hidden;
    }
    .themed-sidebar .brand-link {
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        padding: 16px 10px 14px; border-bottom: 1px solid rgba(255, 255, 255, .15) !important;
        color: var(--primary-text) !important; height: auto; text-align: center;
    }
    .themed-sidebar .brand-logo {
        width: 64px; height: 64px; object-fit: contain; background: #fff;
        border-radius: 14px; padding: 4px; box-shadow: 0 2px 6px rgba(0, 0, 0, .2);
    }
    .themed-sidebar .brand-text { color: var(--primary-text) !important; font-size: 1.15rem; margin: 0; }
    .sidebar-mini.sidebar-collapse .themed-sidebar .brand-logo { width: 38px; height: 38px; }
    .sidebar-mini.sidebar-collapse .themed-sidebar:not(:hover) .brand-text { display: none; }

    .themed-sidebar .sidebar nav > .nav-link,
    .themed-sidebar .sidebar nav .nav-link {
        display: block; margin: 4px 8px; padding: 10px 14px; border-radius: 12px;
        color: var(--primary-text) !important; border: 2px solid transparent;
        transition: background-color .2s ease, transform .15s ease, border-color .2s ease;
    }
    .themed-sidebar .sidebar nav .nav-link p { margin: 0; color: inherit !important; }
    .themed-sidebar .sidebar nav .nav-link:hover {
        background-color: rgba(255, 255, 255, .16) !important; transform: translateX(3px);
    }
    .themed-sidebar .sidebar nav .nav-link.active {
        background-color: var(--secondary) !important; color: var(--secondary-text) !important;
        border-color: var(--primary-dark); box-shadow: 0 3px 8px rgba(0, 0, 0, .18);
    }

    /* ---- Bootstrap greens → institution colours ---- */
    .btn-success, .btn-primary {
        background-color: var(--primary) !important; border-color: var(--primary) !important; color: var(--primary-text) !important;
    }
    .btn-success:hover, .btn-primary:hover, .btn-success:focus, .btn-primary:focus,
    .btn-success:not(:disabled):active, .btn-primary:not(:disabled):active {
        background-color: var(--primary-dark) !important; border-color: var(--primary-dark) !important;
        box-shadow: 0 0 0 .2rem rgba(var(--primary-rgb), .3) !important;
    }
    .btn-outline-success, .btn-outline-primary { color: var(--primary) !important; border-color: var(--primary) !important; background: transparent; }
    .btn-outline-success:hover, .btn-outline-primary:hover { background-color: var(--primary) !important; color: var(--primary-text) !important; }
    .btn-info { background-color: var(--secondary) !important; border-color: var(--secondary) !important; color: var(--secondary-text) !important; }
    .btn-link, a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):not(.brand-link):not(.list-group-item) { color: var(--primary); }
    .text-success, .text-primary { color: var(--primary) !important; }
    .bg-success, .bg-primary { background-color: var(--primary) !important; color: var(--primary-text) !important; }
    .badge-success, .badge-primary { background-color: var(--primary) !important; color: var(--primary-text) !important; }
    .badge-info { background-color: var(--secondary) !important; color: var(--secondary-text) !important; }
    .border-success, .border-primary { border-color: var(--primary) !important; }
    .alert-success { background-color: var(--primary-soft) !important; border-color: var(--primary-soft) !important; color: var(--primary-dark) !important; }
    .page-item.active .page-link { background-color: var(--primary) !important; border-color: var(--primary) !important; color: var(--primary-text) !important; }
    .page-link { color: var(--primary); }
    .dropdown-item.active, .dropdown-item:active { background-color: var(--primary) !important; color: var(--primary-text) !important; }
    .form-control:focus, .custom-select:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 .2rem rgba(var(--primary-rgb), .2) !important; }
    .form-check-input:checked { accent-color: var(--primary); }
    input[type=checkbox], input[type=radio] { accent-color: var(--primary); }
    .thead-light th, .table thead th { border-bottom-color: var(--primary-soft); }
    .card-primary:not(.card-outline) > .card-header, .card-success:not(.card-outline) > .card-header { background-color: var(--primary); color: var(--primary-text); }
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
            @php
                $navInstitutions = Auth::user()->accessibleInstitutions()->get();
                $navInstitutionId = Auth::user()->currentInstitutionId();
                $navInstitution = $navInstitutions->firstWhere('id', $navInstitutionId);
            @endphp
            @if (Auth::user()->isSuperAdmin())
                <li class="nav-item dropdown mr-2">
                    <a class="nav-link dropdown-toggle font-weight-bold text-success" href="#" data-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-university mr-1"></i>
                        {{ $navInstitution->name ?? 'Institution' }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <h6 class="dropdown-header">Switch institution</h6>
                        <form method="POST" action="{{ route('institution.switch') }}">
                            @csrf
                            @foreach ($navInstitutions as $inst)
                                <button type="submit" name="institution_id" value="{{ $inst->id }}"
                                        class="dropdown-item {{ $inst->id === $navInstitutionId ? 'active font-weight-bold' : '' }}">
                                    {{ $inst->name }}
                                </button>
                            @endforeach
                        </form>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('institutions.index') }}"><i class="fas fa-cog mr-1"></i> Manage institutions</a>
                    </div>
                </li>
            @elseif ($navInstitution)
                <li class="nav-item">
                    <span class="nav-link font-weight-bold text-success"><i class="fas fa-university mr-1"></i> {{ $navInstitution->name }}</span>
                </li>
            @endif
            <li class="nav-item dropdown">
                @php
                    $navUser     = Auth::user();
                    $navOwnerId  = $navUser->ownerId();
                    $navAssigned = $navUser->assignedDepartmentIds();
                    $navDepts    = $navAssigned
                        ? \App\Models\Department::whereIn('id', $navAssigned)->orderBy('name')->get()
                        : ($navUser->isSuperAdmin()
                            ? \App\Models\Department::orderBy('name')->get()
                            : \App\Models\Department::where('user_id', $navOwnerId)->orderBy('name')->get());
                    $navScope        = $navUser->scopedDepartmentIds();
                    $navActiveDeptId = $navScope && count($navScope) === 1 ? $navScope[0] : null;
                    $navActiveDept   = $navActiveDeptId ? $navDepts->firstWhere('id', $navActiveDeptId) : null;
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
<aside class="main-sidebar sidebar-dark-success themed-sidebar elevation-4">
    <a href="{{ url('/') }}" class="brand-link">
        @if ($logo = $theme->logoUrl())
            <img src="{{ $logo }}" alt="{{ $theme->institution->name ?? 'Logo' }}" class="brand-logo">
        @endif
        <span class="brand-text font-weight-bold">Lecturer Tracker</span>
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
