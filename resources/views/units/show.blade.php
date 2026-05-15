@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Unit Details</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('units.index') }}">Back</a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        {{-- Unit Info --}}
        <div class="card mb-4">
            <div class="card-header"><strong>Unit Information</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="font-weight-bold">Name</label>
                        <p>{{ $unit->name }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="font-weight-bold">Unit Code</label>
                        <p>{{ $unit->unitCode ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="font-weight-bold">Department</label>
                        <p>{{ $unit->department->name ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lectures Administered --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Lectures Administered for this Unit</strong>
                <span class="badge badge-primary">{{ $lectureAdministereds->count() }} record(s)</span>
            </div>
            <div class="card-body p-0">
                @if($lectureAdministereds->isEmpty())
                    <p class="p-3 text-muted">No lectures administered for this unit yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Lecturer</th>
                                <th>Class</th>
                                <th>Lecture Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lectureAdministereds as $lecture)
                            <tr>
                                <td>{{ $lecture->lecturer->name ?? '—' }}</td>
                                <td>{{ $lecture->classs->name ?? '—' }}</td>
                                <td>{{ $lecture->lecture_date }}</td>
                                <td>{{ $lecture->start_time }}</td>
                                <td>{{ $lecture->end_time }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>
@endsection
