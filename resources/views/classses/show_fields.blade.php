

<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $classs->name }}</p>
</div>

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $classs->created_at }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $classs->updated_at }}</p>
</div>
<div class="card-footer">
    <div class="float-right">
        <a href="{{ route('classses.index') }}" class="btn btn-default">Back</a>
    </div>
</div>
</div>