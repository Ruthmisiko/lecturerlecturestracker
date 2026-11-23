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

    <div class="form-group col-md-6">
        {!! Form::label('travel_budget', 'Travel Budget (KES):') !!}
        {!! Form::number('travel_budget', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => 'e.g. 4000000']) !!}
        <small class="text-muted">Total allocated for staff travel (Staff Visits). Leave empty if this department draws on another's budget.</small>
    </div>

    <div class="form-group col-md-6">
        <label for="travel_budget_parent_id">Draws travel budget from:</label>
        <select name="travel_budget_parent_id" id="travel_budget_parent_id" class="form-control">
            <option value="">— its own budget —</option>
            @foreach (\App\Models\Department::orderBy('name')->get(['id', 'name']) as $d)
                @continue(isset($department) && $department->id === $d->id)
                <option value="{{ $d->id }}" {{ (string) old('travel_budget_parent_id', $department->travel_budget_parent_id ?? '') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">e.g. Accounts and Procurement under Administration: their visits are charged to Administration's budget.</small>
    </div>
@endif

<div class="form-group col-md-12">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'e.g. Health Records and Information Technology']) !!}</div>
