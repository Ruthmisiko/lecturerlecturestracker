<div class="row">
    <div class="form-group col-md-6">
        <label>Lecturer <span class="text-danger">*</span></label>
        <select name="records[{{ $index }}][lecturer_id]" class="form-control lecturer-select" required>
            <option value="">-- Select Lecturer --</option>
            @foreach($lecturers as $lecturer)
                <option value="{{ $lecturer->id }}" data-department="{{ $lecturer->department_id }}">
                    {{ $lecturer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-6">
        <label>Class <span class="text-danger">*</span></label>
        <select name="records[{{ $index }}][classs_id]" class="form-control" required>
            <option value="">-- Select Class --</option>
            @foreach($classes as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-4">
        <label>Start Time <span class="text-danger">*</span></label>
        <input type="time" name="records[{{ $index }}][start_time]" class="form-control" required>
    </div>

    <div class="form-group col-md-4">
        <label>End Time <span class="text-danger">*</span></label>
        <input type="time" name="records[{{ $index }}][end_time]" class="form-control" required>
    </div>

    <div class="form-group col-md-4">
        <label>Unit <small class="text-muted">(optional)</small></label>
        <select name="records[{{ $index }}][unit_id]" class="form-control unit-select">
            <option value="">-- Select Unit --</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" data-department="{{ $unit->department_id }}">
                    {{ $unit->name }}{{ $unit->unitCode ? ' ('.$unit->unitCode.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-12">
        <label>Lecture Date(s) <span class="text-danger">*</span></label>
        <div class="dates-container" id="dates_{{ $index }}">
            <div class="date-item">
                <input type="date" name="records[{{ $index }}][dates][]" class="form-control" required>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="addDate({{ $index }})">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>
</div>
