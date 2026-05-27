<div class="card-body p-0">
    <div class="table-responsive">
        @php
    $dateCounts = $lectureAdministereds->groupBy('lecture_date')->map->count();
@endphp
<div class="card card-body mb-3">
    <form method="GET" action="{{ route('lecture-administereds.index') }}">
    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
    <div class="row align-items-end g-2">

<div class="col-md-2">
    <input type="text" name="lecturer" class="form-control" placeholder="Lecturer" value="{{ request('lecturer') }}">
</div>

<div class="col-md-2">
    <input type="text" name="class" class="form-control" placeholder="Class" value="{{ request('class') }}">
</div>

<div class="col-md-2">
    <input type="date" name="lecture_date" class="form-control" value="{{ request('lecture_date') }}">
</div>

<div class="col-md-2">
    <select name="department_id" class="form-control">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                {{ $dept->name }}
            </option>
        @endforeach
    </select>
</div>

@if(auth()->user()->can('clash.own') || auth()->user()->can('clash.all'))
<div class="col-md-2">
    <select name="status" class="form-control">
        <option value="">Status</option>
        @can('clash.own')
        <option value="own_clash" {{ request('status')=='own_clash' ? 'selected':'' }}>Own Clash</option>
        @endcan
        @can('clash.all')
        <option value="clash_with" {{ request('status')=='clash_with' ? 'selected':'' }}>Clash With Other Lecturer</option>
        @endcan
        <option value="ok" {{ request('status')=='ok' ? 'selected':'' }}>OK</option>
    </select>
</div>
@endif

<!-- BUTTONS -->
<div class="col-md-2 d-flex flex-wrap gap-1">
    <button type="submit" class="btn btn-success btn-sm mr-1">Search</button>
    <a href="{{ route('lecture-administereds.index') }}" class="btn btn-secondary btn-sm mr-1">Reset</a>
    <a href="{{ route('lecture-administereds.export.pdf', request()->all()) }}" class="btn btn-danger btn-sm mr-1">PDF</a>
    <a href="{{ route('lecture-administereds.export.excel', request()->all()) }}" class="btn btn-primary btn-sm">Excel</a>
</div>

</div>
    </form>
</div>
@can('clash.own')
@if((request('lecturer') || request('class') || request('lecture_date')) && $duplicates->isNotEmpty())
    @foreach($duplicates as $dup)
        @php
            $matchesLecturer = request('lecturer') ? str_contains(strtolower($dup->lecturer->name), strtolower(request('lecturer'))) : true;
            $matchesClass = request('class') ? str_contains(strtolower($dup->classs->name), strtolower(request('class'))) : true;
            $matchesDate = request('lecture_date') ? $dup->lecture_date === request('lecture_date') : true;
        @endphp

        @if($matchesLecturer && $matchesClass && $matchesDate)
            <div class="alert alert-warning">
                <strong>LECTURER:</strong> {{ $dup->lecturer->name }} has <strong>double entry</strong>
                for class <strong>{{ $dup->classs->name }}</strong> on
                <strong>{{ \Carbon\Carbon::parse($dup->lecture_date)->format('Y-m-d') }}</strong>.
            </div>
        @endif
    @endforeach
@endif
@endcan

@can('clash.all')
 @if((request('lecturer') || request('class') || request('lecture_date')) && $clashes->isNotEmpty())
                        @foreach($clashes as $clash)
                            @php
                                $matchesClass = request('class') ? str_contains(strtolower($clash->classs->name), strtolower(request('class'))) : true;
                                $matchesDate = request('lecture_date') ? $clash->lecture_date === request('lecture_date') : true;
                            @endphp

                            @if($matchesClass && $matchesDate)
                                <div class="alert alert-danger">
                                    <strong>CLASH:</strong> Class <strong>{{ $clash->classs->name }}</strong> has multiple lecturers
                                    scheduled on <strong>{{ \Carbon\Carbon::parse($clash->lecture_date)->format('Y-m-d') }}</strong>
                                    at <strong>{{ $clash->start_time }} - {{ $clash->end_time }}</strong>.
                                </div>
                            @endif
                        @endforeach
                    @endif
@endcan


        <div class="d-flex align-items-center mb-2">
            <label class="mr-2 mb-0 text-nowrap">Show</label>
            <select class="form-control form-control-sm" style="width:90px"
                    onchange="window.location.href='{{ route('lecture-administereds.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1})">
                @foreach([10, 50, 100, 150, 200] as $opt)
                    <option value="{{ $opt }}" {{ request('per_page', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <span class="ml-2 mb-0 text-nowrap">records per page</span>
        </div>

        <table class="table table-bordered" id="lecturers-table">

            <thead>
                <tr>
                    <th>Lecturer</th>
                    <th>Department</th>
                    <th>Class</th>
                    <th>Unit</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Lecture Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lectureAdministereds as $lectureAdministered)
                    <tr>
                        <td>{{ $lectureAdministered->lecturer->name ?? '-' }}</td>
                        <td>{{ $lectureAdministered->department->name ?? '-' }}</td>
                        <td>{{ $lectureAdministered->classs->name ?? '-' }}</td>
                        <td>{{ $lectureAdministered->unit->name ?? '-' }}</td>
                        <td>{{ $lectureAdministered->start_time }}</td>
                        <td>{{ $lectureAdministered->end_time }}</td>
                        <td>
                            @php $date = $lectureAdministered->lecture_date; @endphp

                            @if($dateCounts[$date] > 1)
                                <span class="badge badge-success">{{ $date }}</span> {{-- duplicate date --}}
                            @else
                                <span class="badge badge-info">{{ $date }}</span>
                            @endif
                        </td>
                        <td>
@php
    // OWN CLASH — same lecturer, same date, overlapping time (any class/unit)
    $ownClash = $ownClashPool->first(function ($c) use ($lectureAdministered) {
        return $c->id !== $lectureAdministered->id &&
               $c->lecturer_id === $lectureAdministered->lecturer_id &&
               $c->lecture_date === $lectureAdministered->lecture_date &&
               !(
                   $lectureAdministered->end_time <= $c->start_time ||
                   $lectureAdministered->start_time >= $c->end_time
               );
    });


    // CLASH WITH OTHER LECTURER — overlapping time, same date, same class, NOT same row
    $clashRecord = $clashes->first(function ($c) use ($lectureAdministered) {
        return $c->id !== $lectureAdministered->id &&                 // prevent self-match
               $c->classs_id === $lectureAdministered->classs_id &&
               $c->lecture_date === $lectureAdministered->lecture_date &&
               $c->lecturer_id !== $lectureAdministered->lecturer_id && // different lecturer
               !(
                   $lectureAdministered->end_time <= $c->start_time ||
                   $lectureAdministered->start_time >= $c->end_time
               );
    });
@endphp

{{-- OWN CLASH --}}
@can('clash.own')
    @if($ownClash)
        <span class="badge badge-danger d-block mb-1">Own Clash</span>
    @endif
@endcan

{{-- CLASH WITH OTHER LECTURER --}}
@can('clash.all')
    @if($clashRecord)
        <span class="badge badge-warning d-block mb-1">
            Clash with {{ $clashRecord->lecturer->name ?? 'Unknown Lecturer' }}
        </span>
    @endif
@endcan

{{-- OK — only when user can see at least one clash type --}}
@if(!$ownClash && !$clashRecord)
    <span class="badge badge-success">OK</span>
@elseif(!auth()->user()->can('clash.own') && !auth()->user()->can('clash.all'))
    <span class="text-muted">—</span>
@endif
</td>


                       <td style="width: 120px">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $lectureAdministered->id }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $lectureAdministered->id }}">
                                @can('lecture.view')
                                <a class="dropdown-item" href="{{ route('lecture-administereds.show', $lectureAdministered->id) }}">
                                    <i class="far fa-eye mr-2 text-primary"></i> View
                                </a>
                                @endcan
                                @can('lecture.edit')
                                <a class="dropdown-item" href="{{ route('lecture-administereds.edit', $lectureAdministered->id) }}">
                                    <i class="far fa-edit mr-2 text-success"></i> Edit
                                </a>
                                @endcan
                                {!! Form::open(['route' => ['lecture-administereds.destroy', $lectureAdministered->id], 'method' => 'delete']) !!}
                                @can('lecture.delete')
                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Are you sure?')">
                                        <i class="far fa-trash-alt mr-2"></i> Delete
                                    </button>
                                @endcan
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $lectureAdministereds])
        </div>
    </div>
</div>

