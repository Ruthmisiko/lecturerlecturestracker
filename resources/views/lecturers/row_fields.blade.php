<div class="row">
    <div class="form-group col-md-4">
        <label>Name <span class="text-danger">*</span></label>
        <input type="text" name="lecturers[{{ $index }}][name]" class="form-control" required>
    </div>

    <div class="form-group col-md-4">
        <label>Email</label>
        <input type="email" name="lecturers[{{ $index }}][email]" class="form-control">
    </div>

    <div class="form-group col-md-4">
        <label>Phone</label>
        <input type="text" name="lecturers[{{ $index }}][phone]" class="form-control">
    </div>

    <div class="form-group col-md-4">
        <label>ID Number</label>
        <input type="number" name="lecturers[{{ $index }}][id_number]" class="form-control">
    </div>

    <div class="form-group col-md-4">
        <label>KRA PIN</label>
        <input type="text" name="lecturers[{{ $index }}][kra_pin]" class="form-control">
    </div>

    <div class="form-group col-md-4">
        <label>Specialization</label>
        <input type="text" name="lecturers[{{ $index }}][specialization]" class="form-control">
    </div>

    @if (Auth::user()->isSuperAdmin())
        <div class="form-group col-md-4">
            <label>Rate per Hour (KES)</label>
            <input type="number" step="0.01" min="0" name="lecturers[{{ $index }}][rate_per_hour]" class="form-control" value="{{ \App\Models\Lecturer::DEFAULT_RATE_PER_HOUR }}">
        </div>

        <div class="form-group col-md-4">
            <label>Tax (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="lecturers[{{ $index }}][tax_rate]" class="form-control" value="{{ \App\Models\Lecturer::DEFAULT_TAX_RATE }}">
        </div>

        <div class="form-group col-md-4">
            <label>Housing Levy (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="lecturers[{{ $index }}][housing_levy_rate]" class="form-control" value="{{ \App\Models\Lecturer::DEFAULT_HOUSING_LEVY_RATE }}">
        </div>
    @endif

    <div class="form-group col-md-8">
        <label>Units</label>
        <div class="units-checkbox-group border rounded p-2" style="height:100px; overflow-y:auto; background:#fff;">
            @foreach($units as $unit)
                <div class="form-check units-item" data-department="{{ $unit->department_id }}">
                    <input class="form-check-input" type="checkbox"
                           name="lecturers[{{ $index }}][units][]"
                           value="{{ $unit->id }}"
                           id="unit_{{ $index }}_{{ $unit->id }}">
                    <label class="form-check-label" for="unit_{{ $index }}_{{ $unit->id }}">
                        {{ $unit->name }}{{ $unit->unitCode ? ' ('.$unit->unitCode.')' : '' }}
                    </label>
                </div>
            @endforeach
            @if($units->isEmpty())
                <span class="text-muted small">No units available</span>
            @endif
        </div>
    </div>
</div>
