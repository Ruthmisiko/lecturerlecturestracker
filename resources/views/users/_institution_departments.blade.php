{{-- Institution picker (superusers) + departments of the chosen institution. Expects $institutions, $departments, $selectedInstitutionId, $selectedDepartmentIds. --}}
@if ($canPickInstitution)
    <div class="mb-3">
        <label>Institution</label>
        <select name="institution_id" id="institutionSelect" class="form-control" required>
            @foreach ($institutions as $inst)
                <option value="{{ $inst->id }}" {{ (int) $selectedInstitutionId === $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">The user will only see this institution's data.</small>
    </div>
@else
    <input type="hidden" id="institutionSelect" value="{{ $selectedInstitutionId }}">
@endif

<div class="mb-3">
    <label>Departments <small class="text-muted">(optional — restricts user to these departments' data; leave all unticked for no restriction)</small></label>
    @foreach ($departments as $dept)
        <div class="form-check dept-option" data-institution="{{ $dept->institution_id }}">
            <input class="form-check-input" type="checkbox" name="departments[]" value="{{ $dept->id }}"
                   id="dept{{ $dept->id }}" {{ in_array($dept->id, $selectedDepartmentIds) ? 'checked' : '' }}>
            <label class="form-check-label" for="dept{{ $dept->id }}">{{ $dept->name }}</label>
        </div>
    @endforeach
    <p class="text-muted mb-0" id="noDepartments" style="display:none;">This institution has no departments yet.</p>
</div>

@push('scripts')
<script>
(function () {
    var select = document.getElementById('institutionSelect');

    function filterDepartments() {
        var visible = 0;
        document.querySelectorAll('.dept-option').forEach(function (el) {
            var show = el.dataset.institution === String(select.value);
            el.style.display = show ? '' : 'none';
            if (!show) el.querySelector('input').checked = false;
            if (show) visible++;
        });
        document.getElementById('noDepartments').style.display = visible ? 'none' : '';
    }

    select.addEventListener('change', filterDepartments);
    filterDepartments();
})();
</script>
@endpush
