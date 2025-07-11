@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create New Role</h2>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="name">Role Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. admin, teacher" required>
        </div>

        <div class="mb-3">
            <label><strong>Assign Permissions</strong></label>
            <div class="row">
                @foreach ($permissions as $permission)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input" id="perm_{{ $loop->index }}">
                            <label class="form-check-label" for="perm_{{ $loop->index }}">
                                {{ $permission->name }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-success">Save Role</button>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
