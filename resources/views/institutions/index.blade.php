@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold">Institutions</h1>
                    <small class="text-muted">Each institution has its own departments, lecturers, units, classes, lectures and users.</small>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-success float-right" href="{{ route('institutions.create') }}">
                        <i class="fas fa-plus mr-1"></i> Add Institution
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('flash::message')

        <div class="card">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Departments</th>
                            <th class="text-center">Lecturers</th>
                            <th class="text-center">Units</th>
                            <th class="text-center">Users</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($institutions as $institution)
                            <tr>
                                <td>
                                    <strong>{{ $institution->name }}</strong>
                                    @if ($institution->id === $currentId)
                                        <span class="badge badge-success ml-1">Current</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $institution->departments_count }}</td>
                                <td class="text-center">{{ $institution->lecturers_count }}</td>
                                <td class="text-center">{{ $institution->units_count }}</td>
                                <td class="text-center">{{ $institution->users_count }}</td>
                                <td class="text-right text-nowrap">
                                    @if ($institution->id !== $currentId)
                                        <form method="POST" action="{{ route('institution.switch') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="institution_id" value="{{ $institution->id }}">
                                            <button class="btn btn-sm btn-outline-success">Switch to</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('institutions.edit', $institution->id) }}" class="btn btn-sm btn-warning">Rename</a>
                                    <form method="POST" action="{{ route('institutions.destroy', $institution->id) }}" class="d-inline"
                                          onsubmit="return confirm('Delete {{ addslashes($institution->name) }}? Only empty institutions can be deleted.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
