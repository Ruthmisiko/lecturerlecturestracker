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
                    <label class="col-sm-2 col-form-label font-weight-bold">Departments:</label>
                    <div class="col-sm-6">
                        <select name="departments[]" id="departments" class="form-control" multiple required>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ in_array($dept->id, old('departments', [])) ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pick one or more. All lecturers below are added to these departments.</small>
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
    var canCopy   = @json(Auth::user()->isSuperAdmin() && Auth::user()->accessibleInstitutions()->count() > 1);
    var lookupUrl = @json(route('lecturers.lookup'));

    // "Copy from another institution": search a lecturer there and fill this row's identity fields.
    function addCopyPicker(rowEl) {
        if (!canCopy || rowEl.querySelector('.copy-lecturer')) return;
        var wrap = document.createElement('div');
        wrap.className = 'form-group col-md-12';
        wrap.innerHTML = '<label>Copy details of a lecturer from another institution</label>' +
            '<select class="form-control copy-lecturer"></select>' +
            '<small class="text-muted">Optional. Fills name, email, phone, ID number, KRA PIN and specialization. Pay rates and units are set for this institution.</small>';
        rowEl.querySelector('.row').insertAdjacentElement('afterbegin', wrap);

        var $sel = $(wrap).find('select');
        $sel.select2({
            theme: 'bootstrap4', width: '100%', allowClear: true, minimumInputLength: 2,
            placeholder: 'Search by name, ID number, email or KRA PIN…',
            ajax: {
                url: lookupUrl, dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term }; },
                processResults: function (list) {
                    return { results: list.map(function (l) {
                        return {
                            id: l.id, data: l, disabled: l.exists_here,
                            text: l.name + (l.id_number ? ' · ID ' + l.id_number : '') + ' (' + l.institution + ')' + (l.exists_here ? ' – already in this institution' : '')
                        };
                    }) };
                }
            }
        });
        $sel.on('select2:select', function (e) {
            var l = e.params.data.data;
            ['name', 'email', 'phone', 'id_number', 'kra_pin', 'specialization'].forEach(function (f) {
                var input = rowEl.querySelector('input[name$="[' + f + ']"]');
                if (input) input.value = l[f] == null ? '' : l[f];
            });
        });
    }

    function addRow() {
        var index = rowCount;
        rowCount++;

        var departmentIds = selectedDepartments();

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
            buildUnitsField(index, departmentIds) +
            '</div></div>';

        document.getElementById('lecturer-rows').insertAdjacentHTML('beforeend', html);
        initUnitsSelect(document.querySelector('.lecturer-row[data-index="' + index + '"] .units-select'));
        addCopyPicker(document.querySelector('.lecturer-row[data-index="' + index + '"]'));
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

    function selectedDepartments() {
        return ($('#departments').val() || []).map(String);
    }

    // <option> tags for units in the chosen departments (all units when none chosen).
    function unitOptions(departmentIds, selected) {
        selected = selected || [];
        return unitsData.filter(function(unit) {
            return !departmentIds.length || departmentIds.indexOf(String(unit.department_id)) !== -1;
        }).map(function(unit) {
            var label = unit.name + (unit.unitCode ? ' (' + unit.unitCode + ')' : '');
            var isSel = selected.indexOf(String(unit.id)) !== -1 ? ' selected' : '';
            return '<option value="' + unit.id + '"' + isSel + '>' + escapeHtml(label) + '</option>';
        }).join('');
    }

    function buildUnitsField(index, departmentIds) {
        return '<div class="form-group col-md-8"><label>Units</label>' +
            '<select name="lecturers[' + index + '][units][]" class="form-control units-select" multiple>' +
            unitOptions(departmentIds) + '</select></div>';
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

    $(function () {
        document.querySelectorAll('.lecturer-row').forEach(addCopyPicker);
        $('#departments').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Select departments…', closeOnSelect: false });

        // Limit each units dropdown to the chosen departments, keeping still-valid selections
        $('#departments').on('change', function() {
            var departmentIds = selectedDepartments();
            $('.units-select').each(function() {
                var selected = ($(this).val() || []).map(String);
                $(this).html(unitOptions(departmentIds, selected)).trigger('change');
            });
        });
    });
</script>
@endpush
