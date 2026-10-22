<div class="form-group col-md-6">
    {!! Form::label('department_id', 'Department:') !!}
    {!! Form::select('department_id', $departments ?? [], null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('phone', 'Phone:') !!}
    {!! Form::text('phone', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('id_number', 'ID Number:') !!}
    {!! Form::number('id_number', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('kra_pin', 'KRA PIN:') !!}
    {!! Form::text('kra_pin', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('specialization', 'Specialization:') !!}
    {!! Form::text('specialization', null, ['class' => 'form-control']) !!}
</div>

@if (Auth::user()->isSuperAdmin())
    <div class="col-md-12"><hr><h6 class="font-weight-bold">Payment</h6></div>

    <div class="form-group col-md-4">
        {!! Form::label('rate_per_hour', 'Rate per Hour (KES):') !!}
        {!! Form::number('rate_per_hour', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0]) !!}
    </div>

    <div class="form-group col-md-4">
        {!! Form::label('tax_rate', 'Tax (%):') !!}
        {!! Form::number('tax_rate', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'max' => 100]) !!}
    </div>

    <div class="form-group col-md-4">
        {!! Form::label('housing_levy_rate', 'Housing Levy (%):') !!}
        {!! Form::number('housing_levy_rate', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'max' => 100]) !!}
    </div>
@endif

<div class="form-group col-md-12">
    <label>Units</label>
    <div class="d-flex flex-wrap" style="gap:8px 24px;">
        @foreach($units ?? [] as $id => $name)
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="units[]" value="{{ $id }}"
                       id="edit_unit_{{ $id }}"
                       {{ in_array($id, $selectedUnits ?? []) ? 'checked' : '' }}>
                <label class="form-check-label" for="edit_unit_{{ $id }}">{{ $name }}</label>
            </div>
        @endforeach
    </div>
</div>
