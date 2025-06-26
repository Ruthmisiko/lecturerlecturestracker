<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $lecturer->name }}</p>
</div>

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $lecturer->email }}</p>
</div>

<!-- Phone Field -->
<div class="col-sm-12">
    {!! Form::label('phone', 'Phone:') !!}
    <p>{{ $lecturer->phone }}</p>
</div>

<!-- ID Number Field -->
<div class="col-sm-12">
    {!! Form::label('id_number', 'ID Number:') !!}
    <p>{{ $lecturer->id_number }}</p>
</div>

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $lecturer->created_at }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $lecturer->updated_at }}</p>
</div>
<!-- KRA Pin Field -->
<div class="col-sm-12">
    {!! Form::label('kra_pin', 'KRA PIN:') !!}
    <p>{{ $lecturer->kra_pin }}</p>
</div>      
             
<div class="mt-4">
    <h4>Lectures Administered</h4>
    @if($lecturer->lectureAdministereds->isEmpty())
        <p class="text-muted">No lectures recorded for this lecturer.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Lecture Date</th>
                    <th>Lecture Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lecturer->lectureAdministereds as $record)
                    <tr>
                        <td>{{ $record->classs->name ?? 'N/A' }}</td>
                        <td>{{ $record->lecture_date }}</td>
                        <td>{{ $record->lecture_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

</div>
<div class="card-footer">
    <div class="float-right">
        <a href="{{ route('lecturers.index') }}" class="btn btn-default">Back</a>
    </div>
</div>