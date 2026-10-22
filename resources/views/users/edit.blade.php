@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit User: {{ $user->name }}</h2>

    <form method="POST" action="{{ route('users.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Assign Role</label>
            <select name="role" class="form-control" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Departments <small class="text-muted">(optional — restricts user to these departments' data; leave all unticked for no restriction)</small></label>
            @php $userDeptIds = old('departments', $user->assignedDepartmentIds()); @endphp
            @forelse ($departments as $dept)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="departments[]" value="{{ $dept->id }}"
                           id="dept{{ $dept->id }}" {{ in_array($dept->id, $userDeptIds) ? 'checked' : '' }}>
                    <label class="form-check-label" for="dept{{ $dept->id }}">{{ $dept->name }}</label>
                </div>
            @empty
                <p class="text-muted mb-0">No departments yet.</p>
            @endforelse
        </div>

        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
