@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Department: {{ $department->name }}</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Name:</strong> {{ $department->name }}
                    </div>
                    <div class="col-md-4">
                        <strong>Code:</strong> {{ $department->code ?? '-' }}
                    </div>
                    <div class="col-md-12 mt-2">
                        <strong>Description:</strong> {{ $department->description ?? '-' }}
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('departments.index') }}" class="btn btn-default">Back</a>
            </div>
        </div>

        @if($department->units->count())
        <div class="card mt-3">
            <div class="card-header"><strong>Units in this Department</strong></div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr><th>Name</th><th>Code</th></tr>
                    </thead>
                    <tbody>
                        @foreach($department->units as $unit)
                        <tr>
                            <td>{{ $unit->name }}</td>
                            <td>{{ $unit->unitCode }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($department->lecturers->count())
        <div class="card mt-3">
            <div class="card-header"><strong>Lecturers in this Department</strong></div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Phone</th></tr>
                    </thead>
                    <tbody>
                        @foreach($department->lecturers as $lecturer)
                        <tr>
                            <td>{{ $lecturer->name }}</td>
                            <td>{{ $lecturer->email }}</td>
                            <td>{{ $lecturer->phone }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
@endsection
