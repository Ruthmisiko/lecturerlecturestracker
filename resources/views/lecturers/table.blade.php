<div class="card-body p-0">
    <div class="card card-body mb-3">
        <form method="GET" action="{{ route('lecturers.index') }}">
            <div class="row align-items-end g-2">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="Search by name"
                           value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
                    <select name="department_id" class="form-control">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex">
                    <button type="submit" class="btn btn-success btn-sm mr-1">Search</button>
                    <a href="{{ route('lecturers.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="lecturers-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>ID Number</th>
                    <th>Specialization</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lecturers as $lecturer)
                    <tr>
                        <td>{{ $lecturer->name }}</td>
                        <td>{{ $lecturer->department->name ?? '-' }}</td>
                        <td>{{ $lecturer->email }}</td>
                        <td>{{ $lecturer->phone }}</td>
                        <td>{{ $lecturer->id_number }}</td>
                        <td>{{ $lecturer->specialization }}</td>
                       <td style="width: 120px">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $lecturer->id }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $lecturer->id }}">
                                <a class="dropdown-item" href="{{ route('lecturers.show', $lecturer->id) }}">
                                    <i class="far fa-eye mr-2 text-primary"></i> View
                                </a>
                                <a class="dropdown-item" href="{{ route('lecturers.edit', $lecturer->id) }}">
                                    <i class="far fa-edit mr-2 text-success"></i> Edit
                                </a>
                                {!! Form::open(['route' => ['lecturers.destroy', $lecturer->id], 'method' => 'delete']) !!}
                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Are you sure?')">
                                        <i class="far fa-trash-alt mr-2"></i> Delete
                                    </button>
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
            @include('adminlte-templates::common.paginate', ['records' => $lecturers])
        </div>
    </div>
</div>

