<div class="form-group col-md-6">
    {!! Form::label('department_id', 'Department:') !!}
    {!! Form::select('department_id', $departments ?? [], null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('unitCode', 'Unit Code:') !!}
    {!! Form::text('unitCode', null, ['class' => 'form-control']) !!}
</div>
