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

@if (Auth::user()->isSuperAdmin())
    <div class="form-group col-md-6">
        {!! Form::label('max_hours', 'Max Hours:') !!}
        {!! Form::number('max_hours', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => 'e.g. 45']) !!}
        <small class="text-muted">Maximum teaching hours allowed for this unit.</small>
    </div>
@endif
