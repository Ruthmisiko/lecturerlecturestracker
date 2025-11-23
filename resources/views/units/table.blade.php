<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-bordered" id="lecturers-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($units as $unit)
                    <tr>
                        <td>{{ $unit->name }}</td>
                       <td style="width: 120px">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $unit->id }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $unit->id }}">

                                <a class="dropdown-item" href="{{ route('units.show', $unit->id) }}">
                                    <i class="far fa-eye mr-2 text-primary"></i> View
                                </a>

                                <a class="dropdown-item" href="{{ route('units.edit', $unit->id) }}">
                                    <i class="far fa-edit mr-2 text-success"></i> Edit
                                </a>

                                {!! Form::open(['route' => ['units.destroy', $unit->id], 'method' => 'delete']) !!}

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
            @include('adminlte-templates::common.paginate', ['records' => $units])
        </div>
    </div>
</div>

