@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Users</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add User</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $canManage = Auth::user()->isSuperAdmin(); @endphp

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Institution</th>
                <th>Department</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="{{ $user->is_active ? '' : 'text-muted' }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->institution->name ?? '—' }}</td>
                    <td>{{ $user->departments->pluck('name')->join(', ') ?: '—' }}</td>
                    <td>
                        @foreach ($user->roles as $role)
                            <span class="badge bg-info text-dark">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Deactivated</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        @if ($canManage && $user->id !== Auth::id())
                            <form method="POST" action="{{ route('users.toggle-active', $user->id) }}" class="d-inline"
                                  onsubmit="return confirm({{ \Illuminate\Support\Js::from($user->is_active ? "Deactivate {$user->name}? They will be signed out and unable to log in." : "Reactivate {$user->name}?") }})">
                                @csrf
                                <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="d-inline"
                                  onsubmit="return confirm({{ \Illuminate\Support\Js::from("Permanently delete {$user->name}? This cannot be undone.") }})">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
