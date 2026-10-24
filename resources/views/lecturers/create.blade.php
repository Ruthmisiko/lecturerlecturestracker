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

@include('lecturers._units_select_assets')

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
    var canSetPay = @json(Auth::user()->isSuperAdmin());

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
            (canSetPay
                ? buildNumberField(index, 'rate_per_hour', 'Rate per Hour (KES)', {{ \App\Models\Lecturer::DEFAULT_RATE_PER_HOUR }}) +
                  buildNumberField(index, 'tax_rate', 'Tax (%)', {{ \App\Models\Lecturer::DEFAULT_TAX_RATE }}) +
                  buildNumberField(index, 'housing_levy_rate', 'Housing Levy (%)', {{ \App\Models\Lecturer::DEFAULT_HOUSING_LEVY_RATE }})
                : '') +
            buildUnitsField(index, departmentId) +
            '</div></div>';

        document.getElementById('lecturer-rows').insertAdjacentHTML('beforeend', html);
        initUnitsSelect(document.querySelector('.lecturer-row[data-index="' + index + '"] .units-select'));
        updateRowNumbers();
    }

    function buildField(index, name, label, type, required) {
        return '<div class="form-group col-md-4">' +
            '<label>' + label + (required ? ' <span class="text-danger">*</span>' : '') + '</label>' +
            '<input type="' + type + '" name="lecturers[' + index + '][' + name + ']" class="form-control"' +
            (required ? ' required' : '') + '>' +
            '</div>';
    }

    function buildNumberField(index, name, label, value) {
        return '<div class="form-group col-md-4">' +
            '<label>' + label + '</label>' +
            '<input type="number" step="0.01" min="0" name="lecturers[' + index + '][' + name + ']" class="form-control" value="' + value + '">' +
            '</div>';
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // <option> tags for units in the chosen department (all units when none chosen).
    function unitOptions(departmentId, selected) {
        selected = selected || [];
        return unitsData.filter(function(unit) {
            return !departmentId || unit.department_id == departmentId;
        }).map(function(unit) {
            var label = unit.name + (unit.unitCode ? ' (' + unit.unitCode + ')' : '');
            var isSel = selected.indexOf(String(unit.id)) !== -1 ? ' selected' : '';
            return '<option value="' + unit.id + '"' + isSel + '>' + escapeHtml(label) + '</option>';
        }).join('');
    }

    function buildUnitsField(index, departmentId) {
        return '<div class="form-group col-md-8"><label>Units</label>' +
            '<select name="lecturers[' + index + '][units][]" class="form-control units-select" multiple>' +
            unitOptions(departmentId) + '</select></div>';
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

    // Limit each units dropdown to the chosen department, keeping still-valid selections
    document.getElementById('department_id').addEventListener('change', function() {
        var departmentId = this.value;
        $('.units-select').each(function() {
            var selected = ($(this).val() || []).map(String);
            $(this).html(unitOptions(departmentId, selected)).trigger('change');
        });
    });
</script>
@endpush
