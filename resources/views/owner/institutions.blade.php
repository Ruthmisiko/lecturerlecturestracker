@extends('owner.layout')
@section('title', 'Institutions')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:10px;">
    <div>
        <h3 class="font-weight-bold mb-0">Institutions</h3>
        <small class="text-muted">Deactivating an institution signs out and blocks all of its users.</small>
    </div>
    <form method="GET" class="d-flex flex-wrap" style="gap:6px;">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name…" style="width:200px;">
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
                <tr>
                    <th>Institution</th><th>Account (owner login)</th><th class="text-center">Departments</th>
                    <th class="text-center">Lecturers</th><th class="text-center">Users</th><th>Created</th><th>Status</th><th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($institutions as $i)
                    <tr>
                        <td class="font-weight-bold">{{ $i->name }}</td>
                        <td class="small">{{ $i->owner->name ?? '—' }}<div class="text-muted">{{ $i->owner->email ?? '' }}</div></td>
                        <td class="text-center">{{ $i->departments_count }}</td>
                        <td class="text-center">{{ $i->lecturers_count }}</td>
                        <td class="text-center">
                            <a href="{{ route('owner.users', ['institution_id' => $i->id]) }}">{{ $i->users_count }}</a>
                        </td>
                        <td class="small text-muted">{{ $i->created_at?->format('d M Y') }}</td>
                        <td><span class="badge {{ $i->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $i->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('owner.institutions.toggle', $i->id) }}"
                                  onsubmit="return confirm('{{ $i->is_active ? 'Deactivate' : 'Activate' }} {{ addslashes($i->name) }}?')">
                                @csrf
                                @if ($i->is_active)
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban mr-1"></i>Deactivate</button>
                                @else
                                    <button class="btn btn-sm btn-orange"><i class="fas fa-check mr-1"></i>Activate</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted p-5">No institutions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($institutions->hasPages())<div class="card-footer bg-white">{{ $institutions->links('pagination::bootstrap-4') }}</div>@endif
</div>
@endsection
