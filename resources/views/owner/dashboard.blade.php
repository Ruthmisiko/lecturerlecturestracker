@extends('owner.layout')
@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
    <div>
        <h3 class="font-weight-bold mb-0">System Overview</h3>
        <small class="text-muted">Every institution and user on Lecturer Tracker</small>
    </div>
</div>

<div class="row">
    @foreach ([
        ['Institutions', $stats['institutions'], $stats['institutions_inactive'] . ' deactivated', 'fa-university', '#ecebf8', '#2d2a6b', route('owner.institutions')],
        ['Users', $stats['users'], $stats['users_inactive'] . ' deactivated', 'fa-users', '#fdeee7', '#f26a2e', route('owner.users')],
        ['Lecturers', $stats['lecturers'], 'across all institutions', 'fa-chalkboard-teacher', '#e6f6ec', '#18794e', null],
        ['Lecture records', $stats['lectures'], 'across all institutions', 'fa-clock', '#eaf2fd', '#1c5cab', null],
    ] as [$label, $value, $sub, $icon, $bg, $fg, $link])
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card stat h-100">
                <div class="card-body">
                    <div>
                        <p class="label">{{ $label }}</p>
                        <p class="value">{{ number_format($value) }}</p>
                        <small class="text-muted">{{ $sub }}</small>
                        @if ($link)<div><a href="{{ $link }}" class="small font-weight-bold">Manage →</a></div>@endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white font-weight-bold">Newest institutions</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Account</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($recentInstitutions as $i)
                            <tr>
                                <td class="font-weight-bold">{{ $i->name }}</td>
                                <td class="small text-muted">{{ $i->owner->email ?? '—' }}</td>
                                <td><span class="badge {{ $i->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $i->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted p-4">No institutions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white font-weight-bold">Newest users</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Institution</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($recentUsers as $u)
                            <tr>
                                <td><span class="font-weight-bold">{{ $u->name }}</span><div class="small text-muted">{{ $u->email }}</div></td>
                                <td class="small">{{ $u->institution->name ?? '—' }}</td>
                                <td><span class="badge {{ $u->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $u->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted p-4">No users yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
