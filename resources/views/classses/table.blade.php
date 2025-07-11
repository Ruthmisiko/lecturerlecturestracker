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
                @foreach($classses as $classs)
                    <tr>
                        <td>{{ $classs->name }}</td>
                       
                       <td style="width: 120px">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $classs->id }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $classs->id }}">
                                @can('class.view')
                                <a class="dropdown-item" href="{{ route('classses.show', $classs->id) }}">
                                    <i class="far fa-eye mr-2 text-primary"></i> View
                                </a>
                                @endcan
                                @can('class.edit')
                                <a class="dropdown-item" href="{{ route('classses.edit', $classs->id) }}">
                                    <i class="far fa-edit mr-2 text-success"></i> Edit
                                </a>
                                @endcan
                                {!! Form::open(['route' => ['classses.destroy', $classs->id], 'method' => 'delete']) !!}
                                @can('class.delete')
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
            @include('adminlte-templates::common.paginate', ['records' => $classses])
        </div>
    </div>
</div>

