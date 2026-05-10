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
