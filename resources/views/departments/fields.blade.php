<div class="form-group col-md-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('code', 'Code:') !!}
    {!! Form::text('code', null, ['class' => 'form-control']) !!}
</div>

@if (Auth::user()->isSuperAdmin())
    <div class="form-group col-md-6">
        {!! Form::label('approved_hours', 'Approved Hours:') !!}
        {!! Form::number('approved_hours', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => 'e.g. 500']) !!}
        <small class="text-muted">Total teaching hours approved for this department.</small>
    </div>
@endif

<div class="form-group col-md-12">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'e.g. Health Records and Information Technology']) !!}
    <small class="text-muted">Used in payment narrations: "…rendered in the Department of <em>description</em>…". Leave empty to use the department name.</small>
</div>
