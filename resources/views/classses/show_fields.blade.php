

<!-- Name Field -->
<div class="form-group col-md-6">
    {!! Form::label('name', 'Class Name:') !!}
    <p>{{ $classs->name }}</p>
</div>

<div class="form-group col-md-12">
    {!! Form::label('units', 'Units:') !!}
    @if($classs->units->isNotEmpty())
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Unit Name</th>
                    <th>Unit Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classs->units as $index => $unit)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $unit->name }}</td>
                        <td>{{ $unit->unitCode ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No units assigned to this class.</p>
    @endif
</div>

<div class="card-footer">
    <div class="float-right">
        <a href="{{ route('classses.index') }}" class="btn btn-default">Back</a>
    </div>
</div>
</div>