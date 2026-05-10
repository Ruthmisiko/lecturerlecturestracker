@extends('layouts.app')

@push('styles')
<style>
    .la-row {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
    }
    .remove-row-btn {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .row-number {
        font-weight: bold;
        color: #495057;
        margin-bottom: 10px;
    }
    .dates-container .date-item {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }
    .dates-container .date-item input {
        flex: 1;
    }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Create Lecture Administered</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::open(['route' => 'lecture-administereds.store']) !!}

            <div class="card-body">

                {{-- Department at top --}}
                <div class="form-group row align-items-center">
                    <label class="col-sm-2 col-form-label font-weight-bold">Department:</label>
                    <div class="col-sm-4">
                        <select name="department_id" id="department_id" class="form-control">
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                {{-- Dynamic rows --}}
                <div id="la-rows">
                    <div class="la-row" data-index="0">
                        <div class="row-number">Record #1</div>
                        <button type="button" class="btn btn-sm btn-danger remove-row-btn" onclick="removeRow(this)" style="display:none;">
                            <i class="fas fa-times"></i>
                        </button>
                        @include('lecture_administereds.row_fields', ['index' => 0, 'lecturers' => $lecturers, 'classes' => $classes, 'units' => $units])
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary mt-2" onclick="addRow()">
                    <i class="fas fa-plus"></i> Add Lecture Administered
                </button>

            </div>

            <div class="card-footer">
                {!! Form::submit('Save All', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('lecture-administereds.index') }}" class="btn btn-default"> Cancel </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection

@push('scripts')
<script>
    var rowCount = 1;
    var lecturersData = @json($lecturers);
    var classesData   = @json($classes);
    var unitsData     = @json($units);

    function addRow() {
        var index = rowCount++;
        var deptId = document.getElementById('department_id').value;

        var lecturerOptions = buildLecturerOptions(deptId, index);
        var classOptions    = buildClassOptions();

        var html =
            '<div class="la-row" data-index="' + index + '">' +
            '<div class="row-number">Record #' + (rowCount) + '</div>' +
            '<button type="button" class="btn btn-sm btn-danger remove-row-btn" onclick="removeRow(this)">' +
            '<i class="fas fa-times"></i></button>' +
            '<div class="row">' +

            '<div class="form-group col-md-6">' +
            '<label>Lecturer <span class="text-danger">*</span></label>' +
            '<select name="records[' + index + '][lecturer_id]" class="form-control lecturer-select" required>' +
            '<option value="">-- Select Lecturer --</option>' + lecturerOptions + '</select></div>' +

            '<div class="form-group col-md-6">' +
            '<label>Class <span class="text-danger">*</span></label>' +
            '<select name="records[' + index + '][classs_id]" class="form-control" required>' +
            '<option value="">-- Select Class --</option>' + classOptions + '</select></div>' +

            '<div class="form-group col-md-4">' +
            '<label>Start Time <span class="text-danger">*</span></label>' +
            '<input type="time" name="records[' + index + '][start_time]" class="form-control" required></div>' +

            '<div class="form-group col-md-4">' +
            '<label>End Time <span class="text-danger">*</span></label>' +
            '<input type="time" name="records[' + index + '][end_time]" class="form-control" required></div>' +

            '<div class="form-group col-md-4">' +
            '<label>Unit <small class="text-muted">(optional)</small></label>' +
            '<select name="records[' + index + '][unit_id]" class="form-control unit-select">' +
            '<option value="">-- Select Unit --</option>' + buildUnitOptions(deptId) + '</select></div>' +

            '<div class="form-group col-md-12">' +
            '<label>Lecture Date(s) <span class="text-danger">*</span></label>' +
            '<div class="dates-container" id="dates_' + index + '">' +
            '<div class="date-item">' +
            '<input type="date" name="records[' + index + '][dates][]" class="form-control" required>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDate(' + index + ')"><i class="fas fa-plus"></i></button>' +
            '</div></div></div>' +

            '</div></div>';

        document.getElementById('la-rows').insertAdjacentHTML('beforeend', html);
        updateRowNumbers();
    }

    function buildLecturerOptions(deptId, index) {
        var html = '';
        lecturersData.forEach(function(l) {
            if (!deptId || l.department_id == deptId) {
                html += '<option value="' + l.id + '">' + l.name + '</option>';
            }
        });
        return html;
    }

    function buildClassOptions() {
        var html = '';
        Object.entries(classesData).forEach(function([id, name]) {
            html += '<option value="' + id + '">' + name + '</option>';
        });
        return html;
    }

    function buildUnitOptions(deptId) {
        var html = '';
        unitsData.forEach(function(u) {
            if (!deptId || u.department_id == deptId) {
                var label = u.name + (u.unitCode ? ' (' + u.unitCode + ')' : '');
                html += '<option value="' + u.id + '">' + label + '</option>';
            }
        });
        return html;
    }

    function addDate(index) {
        var container = document.getElementById('dates_' + index);
        var item = document.createElement('div');
        item.className = 'date-item';
        item.innerHTML =
            '<input type="date" name="records[' + index + '][dates][]" class="form-control" required>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'.date-item\').remove()"><i class="fas fa-times"></i></button>';
        container.appendChild(item);
    }

    function removeRow(btn) {
        btn.closest('.la-row').remove();
        updateRowNumbers();
    }

    function updateRowNumbers() {
        var rows = document.querySelectorAll('.la-row');
        rows.forEach(function(row, i) {
            row.querySelector('.row-number').textContent = 'Record #' + (i + 1);
            row.querySelector('.remove-row-btn').style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
    }

    // Filter lecturers and units by department across all existing rows
    document.getElementById('department_id').addEventListener('change', function() {
        var deptId = this.value;

        document.querySelectorAll('.lecturer-select').forEach(function(select) {
            var current = select.value;
            select.innerHTML = '<option value="">-- Select Lecturer --</option>';
            lecturersData.forEach(function(l) {
                if (!deptId || l.department_id == deptId) {
                    var opt = document.createElement('option');
                    opt.value = l.id;
                    opt.text  = l.name;
                    if (l.id == current) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        });

        document.querySelectorAll('.unit-select').forEach(function(select) {
            var current = select.value;
            select.innerHTML = '<option value="">-- Select Unit --</option>';
            unitsData.forEach(function(u) {
                if (!deptId || u.department_id == deptId) {
                    var opt = document.createElement('option');
                    opt.value = u.id;
                    opt.text  = u.name + (u.unitCode ? ' (' + u.unitCode + ')' : '');
                    if (u.id == current) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        });
    });
</script>
@endpush
