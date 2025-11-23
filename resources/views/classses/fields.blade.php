<div class="form-group col-md-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('code', 'Code:') !!}
    {!! Form::text('code', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-md-6">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::text('description', null, ['class' => 'form-control']) !!}
</div>
<div class="form-group col-md-6">
    {!! Form::label('units', 'Units:') !!}
    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 4px;">
        @foreach($units as $unit)
            <div class="form-check">
                {!! Form::checkbox(
                    'units[]',
                    $unit->id,
                    isset($selectedUnits) && in_array($unit->id, $selectedUnits),
                    ['class' => 'form-check-input', 'id' => 'unit_'.$unit->id]
                ) !!}
                {!! Form::label('unit_'.$unit->id, $unit->name, ['class' => 'form-check-label']) !!}
            </div>
        @endforeach
    </div>
</div>


