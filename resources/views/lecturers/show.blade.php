@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Lecturer Details</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="row">

        {{-- LEFT COLUMN — LECTURER DETAILS --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Profile Details</h5>
                </div>

                <div class="card-body">

                    <div class="mb-3">
                        {!! Form::label('name', 'Name:') !!}
                        <p class="fw-bold">{{ $lecturer->name }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('email', 'Email:') !!}
                        <p class="fw-bold">{{ $lecturer->email }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('phone', 'Phone:') !!}
                        <p class="fw-bold">{{ $lecturer->phone }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('id_number', 'ID Number:') !!}
                        <p class="fw-bold">{{ $lecturer->id_number }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('kra_pin', 'KRA PIN:') !!}
                        <p class="fw-bold">{{ $lecturer->kra_pin }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('created_at', 'Created At:') !!}
                        <p>{{ $lecturer->created_at }}</p>
                    </div>

                    <div class="mb-3">
                        {!! Form::label('updated_at', 'Updated At:') !!}
                        <p>{{ $lecturer->updated_at }}</p>
                    </div>

                </div>

                <div class="card-footer text-end">
                    <a href="{{ route('lecturers.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </div>


        {{-- RIGHT COLUMN — LECTURES TABLE --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between">
                    <h5 class="mb-0">Lectures Administered</h5>
                </div>

                <div class="card-body">

                    @if($lecturer->lectureAdministereds->isEmpty())
                        <p class="text-muted">No lectures recorded for this lecturer.</p>
                    @else
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Class</th>
            <th>Lecture Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lecturer->lectureAdministereds as $index => $record)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $record->classs->name ?? 'N/A' }}</td>
            <td>{{ $record->lecture_date }}</td>
            <td>{{ $record->start_time }}</td>
            <td>{{ $record->end_time }}</td>

            <td>
                @if($record->status == 'OK')
                    <span class="badge bg-success">OK</span>
                @elseif(str_contains($record->status, 'Own Clash'))
                    <span class="badge bg-warning text-dark">{{ $record->status }}</span>
                @elseif(str_contains($record->status, 'Clash'))
                    <span class="badge bg-danger text-white">{{ $record->status }}</span>
                @else
                    <span class="badge bg-secondary">{{ $record->status }}</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
