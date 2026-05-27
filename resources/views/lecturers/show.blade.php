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

        {{-- LEFT COLUMN — PROFILE + ASSIGNED UNITS --}}
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
                        {!! Form::label('department', 'Department:') !!}
                        <p class="fw-bold">{{ $lecturer->department->name ?? '-' }}</p>
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

                    {{-- Assigned Units --}}
                    <hr>
                    <h6 class="font-weight-bold">Assigned Units</h6>
                    @if($lecturerUnits->isEmpty())
                        <p class="text-muted">No units assigned.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($lecturerUnits as $unit)
                                <li class="mb-1">
                                    <span class="badge badge-success">{{ $unit->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="card-footer">
                    <a href="{{ route('lecturers.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN — LECTURES TABLE --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lectures Administered</h5>
                    <div>
                        <a href="{{ route('lecturers.export.pdf', array_merge(['id' => $lecturer->id], request()->all())) }}"
                           class="btn btn-danger btn-sm mr-1">
                            <i class="fas fa-file-pdf mr-1"></i> PDF
                        </a>
                        <a href="{{ route('lecturers.export.excel', array_merge(['id' => $lecturer->id], request()->all())) }}"
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    {{-- Filters --}}
                    <form action="{{ route('lecturers.show', $lecturer->id) }}" method="GET" class="mb-3">
                        <div class="row g-2">

                            <div class="col-md-3">
                                <input type="date" name="from_date" class="form-control form-control-sm"
                                       placeholder="From Date" value="{{ request('from_date') }}">
                            </div>

                            <div class="col-md-3">
                                <input type="date" name="to_date" class="form-control form-control-sm"
                                       placeholder="To Date" value="{{ request('to_date') }}">
                            </div>

                            <div class="col-md-3">
                                <input type="text" name="class" class="form-control form-control-sm"
                                       placeholder="Class" value="{{ request('class') }}">
                            </div>

                            <div class="col-md-3">
                                <select name="unit_id" class="form-control form-control-sm">
                                    <option value="">All Units</option>
                                    @foreach($lecturerUnits as $unit)
                                        <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="status" class="form-control form-control-sm">
                                    <option value="">All Statuses</option>
                                    <option value="ok"         {{ request('status') === 'ok'         ? 'selected' : '' }}>OK</option>
                                    <option value="own_clash"  {{ request('status') === 'own_clash'  ? 'selected' : '' }}>Own Clash</option>
                                    <option value="clash_with" {{ request('status') === 'clash_with' ? 'selected' : '' }}>Clash With Other Lecturer</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-flex">
                                <button type="submit" class="btn btn-success btn-sm mr-1">Filter</button>
                                <a href="{{ route('lecturers.show', $lecturer->id) }}" class="btn btn-secondary btn-sm">Reset</a>
                            </div>

                        </div>
                    </form>

                    @if($lectures->isEmpty())
                        <p class="text-muted">No lectures match the selected filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Class</th>
                                        <th>Unit</th>
                                        <th>Lecture Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lectures as $index => $record)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $record->classs->name ?? 'N/A' }}</td>
                                        <td>{{ $record->unit->name ?? '-' }}</td>
                                        <td>{{ $record->lecture_date }}</td>
                                        <td>{{ $record->start_time }}</td>
                                        <td>{{ $record->end_time }}</td>
                                        <td>
                                            @if($record->computed_status === 'ok')
                                                <span class="badge badge-success">OK</span>
                                            @elseif($record->computed_status === 'own_clash')
                                                <span class="badge badge-warning text-dark">Own Clash</span>
                                            @elseif($record->computed_status === 'clash_with')
                                                <span class="badge badge-danger">
                                                    Clash with: {{ $record->clash_with_names }}
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    Own Clash &amp; Clash with: {{ $record->clash_with_names }}
                                                </span>
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
