<div class="card-body p-0">
    <div class="table-responsive">
        @php
    $dateCounts = $lectureAdministereds->groupBy('lecture_date')->map->count();
@endphp
<div class="card card-body mb-3">
    <form method="GET" action="{{ route('lecture-administereds.index') }}">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="lecturer" class="form-control" placeholder="Lecturer name" value="{{ request('lecturer') }}">
            </div>
            <div class="col-md-3">
                <input type="text" name="class" class="form-control" placeholder="Class name" value="{{ request('class') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="lecture_date" class="form-control" value="{{ request('lecture_date') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success mr-2">Search</button>
                <a href="{{ route('lecture-administereds.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>
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


        <table class="table table-bordered" id="lecturers-table">

            <thead>
                <tr>
                    <th>Lecturer</th>
                    <th>Class</th>
                    <th>Lecture Start Time</th>
                    <th> Lecture End Time</th>
                    <th>Lecture Date</th>
                     <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lectureAdministereds as $lectureAdministered)
                    <tr>
                        <td>{{ $lectureAdministered->lecturer->name }}</td>
                        <td>{{ $lectureAdministered->classs->name }}</td>
                        <td>{{ $lectureAdministered->start_time }}</td>
                        <td>{{ $lectureAdministered->end_time }}</td>
                        <td>
                            @php $date = $lectureAdministered->lecture_date; @endphp

                            @if($dateCounts[$date] > 1)
                                <span class="badge badge-danger">{{ $date }}</span> {{-- duplicate date --}}
                            @else
                                <span class="badge badge-info">{{ $date }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $isDoubleEntry = $duplicates->contains(function ($dup) use ($lectureAdministered) {
                                    return $dup->lecturer_id == $lectureAdministered->lecturer_id &&
                                            $dup->classs_id == $lectureAdministered->classs_id &&
                                            $dup->lecture_date == $lectureAdministered->lecture_date &&
                                            $dup->start_time == $lectureAdministered->start_time &&
                                            $dup->end_time == $lectureAdministered->end_time;
                                });

                                $isClash = $clashes->contains(function ($clash) use ($lectureAdministered) {
                                    return $clash->classs_id == $lectureAdministered->classs_id &&
                                            $clash->lecture_date == $lectureAdministered->lecture_date &&
                                            $clash->start_time == $lectureAdministered->start_time &&
                                            $clash->end_time == $lectureAdministered->end_time;
                                });
                            @endphp

                            @if($isDoubleEntry)
                                <span class="badge badge-danger">Double Entry</span>
                            @elseif($isClash)
                                <span class="badge badge-warning">Clash</span>
                            @else
                                <span class="badge badge-success">OK</span>
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

