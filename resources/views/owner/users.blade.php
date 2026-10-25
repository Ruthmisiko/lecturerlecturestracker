@extends('owner.layout')
@section('title', 'Users')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:10px;">
    <div>
        <h3 class="font-weight-bold mb-0">Users</h3>
        <small class="text-muted">All users in every institution. Deactivated users can't log in.</small>
    </div>
    <form method="GET" class="d-flex flex-wrap" style="gap:6px;">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name or email…" style="width:220px;">
        <select name="institution_id" class="form-control form-control-sm" style="width:200px;">
            <option value="">All institutions</option>
            @foreach ($institutionOptions as $opt)
                <option value="{{ $opt->id }}" {{ request('institution_id') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control form-control-sm" style="width:140px;">
            <option value="">All statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Deactivated</option>
        </select>
        <button class="btn btn-sm btn-navy">Filter</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Institution</th><th>Role</th><th>Created</th><th>Status</th><th class="text-right">Action</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-weight-bold">
                            {{ $u->name }}
                            @if (!$u->user_id)<span class="badge badge-light border ml-1" title="Top-level account owner">Account owner</span>@endif
                        </td>
                        <td class="small">{{ $u->email }}</td>
                        <td class="small">
                            {{ $u->institution->name ?? '—' }}
                            @if ($u->institution && !$u->institution->is_active)<span class="badge badge-inactive ml-1">institution off</span>@endif
                        </td>
                        <td class="small">{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="small text-muted">{{ $u->created_at?->format('d M Y') }}</td>
                        <td><span class="badge {{ $u->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $u->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('owner.users.toggle', $u->id) }}"
                                  onsubmit="return confirm('{{ $u->is_active ? 'Deactivate' : 'Activate' }} {{ addslashes($u->name) }}?')">
                                @csrf
                                @if ($u->is_active)
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-user-slash mr-1"></i>Deactivate</button>
                                @else
                                    <button class="btn btn-sm btn-orange"><i class="fas fa-user-check mr-1"></i>Activate</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted p-5">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())<div class="card-footer bg-white">{{ $users->links('pagination::bootstrap-4') }}</div>@endif
</div>
@endsection
