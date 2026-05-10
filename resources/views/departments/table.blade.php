<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-bordered" id="departments-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $department)
                    <tr>
                        <td>{{ $department->name }}</td>
                        <td>{{ $department->code }}</td>
                        <td>{{ $department->description }}</td>
                        <td style="width: 120px">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                                        id="dropdownMenuButton{{ $department->id }}"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $department->id }}">
                                    <a class="dropdown-item" href="{{ route('departments.show', $department->id) }}">
                                        <i class="far fa-eye mr-2 text-primary"></i> View
                                    </a>
                                    <a class="dropdown-item" href="{{ route('departments.edit', $department->id) }}">
                                        <i class="far fa-edit mr-2 text-success"></i> Edit
                                    </a>
                                    {!! Form::open(['route' => ['departments.destroy', $department->id], 'method' => 'delete']) !!}
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
            @include('adminlte-templates::common.paginate', ['records' => $departments])
        </div>
    </div>
</div>
