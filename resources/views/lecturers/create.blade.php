@extends('layouts.app')

@push('styles')
<style>
    .lecturer-row {
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
    select[multiple] {
        min-height: 100px;
    }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Create Lecturers</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::open(['route' => 'lecturers.store', 'id' => 'lecturer-form']) !!}

            <div class="card-body">

                {{-- Department selector at top --}}
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label font-weight-bold">Department:</label>
                    <div class="col-sm-4">
                        <select name="department_id" id="department_id" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                {{-- Dynamic lecturer rows --}}
                <div id="lecturer-rows">
                    <div class="lecturer-row" data-index="0">
                        <div class="row-number">Lecturer #1</div>
                        <button type="button" class="btn btn-sm btn-danger remove-row-btn" onclick="removeRow(this)" style="display:none;">
                            <i class="fas fa-times"></i>
                        </button>
                        @include('lecturers.row_fields', ['index' => 0])
                    </div>
                </div>

                <button type="button" class="btn btn-outline-success mt-2" onclick="addRow()">
                    <i class="fas fa-plus"></i> Add Another Lecturer
                </button>

            </div>

            <div class="card-footer">
                {!! Form::submit('Save All', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('lecturers.index') }}" class="btn btn-default"> Cancel </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection

@push('scripts')
<script>
    var rowCount = 1;
    var unitsData = @json($units);

    function addRow() {
        var index = rowCount;
        rowCount++;

        var departmentId = document.getElementById('department_id').value;

        var html = '<div class="lecturer-row" data-index="' + index + '">' +
            '<div class="row-number">Lecturer #' + rowCount + '</div>' +
            '<button type="button" class="btn btn-sm btn-danger remove-row-btn" onclick="removeRow(this)">' +
            '<i class="fas fa-times"></i></button>' +
            '<div class="row">' +
            buildField(index, 'name', 'Name', 'text', true) +
            buildField(index, 'email', 'Email', 'email', false) +
            buildField(index, 'phone', 'Phone', 'text', false) +
            buildField(index, 'id_number', 'ID Number', 'number', false) +
            buildField(index, 'kra_pin', 'KRA PIN', 'text', false) +
            buildField(index, 'specialization', 'Specialization', 'text', false) +
            buildUnitsField(index, departmentId) +
            '</div></div>';

        document.getElementById('lecturer-rows').insertAdjacentHTML('beforeend', html);
        updateRowNumbers();
    }

    function buildField(index, name, label, type, required) {
        return '<div class="form-group col-md-4">' +
            '<label>' + label + (required ? ' <span class="text-danger">*</span>' : '') + '</label>' +
            '<input type="' + type + '" name="lecturers[' + index + '][' + name + ']" class="form-control"' +
            (required ? ' required' : '') + '>' +
            '</div>';
    }

    function buildUnitsField(index, departmentId) {
        var checkboxes = '';
        unitsData.forEach(function(unit) {
            if (!departmentId || unit.department_id == departmentId) {
                var label = unit.name + (unit.unitCode ? ' (' + unit.unitCode + ')' : '');
                checkboxes += '<div class="form-check units-item" data-department="' + (unit.department_id || '') + '">' +
                    '<input class="form-check-input" type="checkbox" ' +
                    'name="lecturers[' + index + '][units][]" value="' + unit.id + '" ' +
                    'id="unit_' + index + '_' + unit.id + '">' +
                    '<label class="form-check-label" for="unit_' + index + '_' + unit.id + '">' + label + '</label>' +
                    '</div>';
            }
        });
        if (!checkboxes) checkboxes = '<span class="text-muted small">No units available</span>';
        return '<div class="form-group col-md-8"><label>Units</label>' +
            '<div class="units-checkbox-group border rounded p-2" style="height:100px;overflow-y:auto;background:#fff;">' +
            checkboxes + '</div></div>';
    }

    function removeRow(btn) {
        btn.closest('.lecturer-row').remove();
        updateRowNumbers();
    }

    function updateRowNumbers() {
        var rows = document.querySelectorAll('.lecturer-row');
        rows.forEach(function(row, i) {
            row.querySelector('.row-number').textContent = 'Lecturer #' + (i + 1);
            var removeBtn = row.querySelector('.remove-row-btn');
            removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
    }

    // Filter unit checkboxes when department changes
    document.getElementById('department_id').addEventListener('change', function() {
        var departmentId = this.value;
        document.querySelectorAll('.units-item').forEach(function(item) {
            var itemDept = item.dataset.department;
            var show = !departmentId || itemDept == departmentId;
            item.style.display = show ? '' : 'none';
            if (!show) {
                var cb = item.querySelector('input[type=checkbox]');
                if (cb) cb.checked = false;
            }
        });
    });
</script>
@endpush
