@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Upload Attendance Sheets</h1>
            <small class="text-muted">The system reads the <strong>lecture date</strong> and <strong>time</strong> from each sheet. You check them before anything is saved.</small>
        </div>
        <a href="{{ route('lecture-administereds.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Lecture Administereds</a>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    @unless ($configured)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Reading sheets is not set up yet: the server needs a <strong>GEMINI_API_KEY</strong> in its <code>.env</code> file.
        </div>
    @endunless

    <form method="POST" action="{{ route('attendance-upload.read') }}" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <div class="card">
            <div class="card-header font-weight-bold">1. Whose sheets are these?</div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Lecturer <span class="text-danger">*</span></label>
                        <select name="lecturer_id" id="lecturerSelect" class="form-control" required>
                            <option value="">-- Select Lecturer --</option>
                            @foreach ($lecturers as $l)
                                <option value="{{ $l->id }}" data-department="{{ $l->department_id }}" {{ old('lecturer_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Department</label>
                        <select name="department_id" id="departmentSelect" class="form-control">
                            <option value="">-- Select Department --</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Semester</label>
                        <select name="semester_id" class="form-control">
                            @forelse ($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id', $s->is_current ? $s->id : null) == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}{{ $s->is_current ? ' (current)' : '' }}
                                </option>
                            @empty
                                <option value="">-- No semesters yet --</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Unit</label>
                        <select name="unit_id" id="unitSelect" class="form-control">
                            <option value="">-- Select Unit --</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" data-department="{{ $u->department_id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}{{ $u->unitCode ? ' (' . $u->unitCode . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="classs_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" {{ old('classs_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header font-weight-bold">2. Attendance sheets</div>
            <div class="card-body">
                <input type="file" name="files[]" id="filesInput" class="form-control-file" multiple required
                       accept="image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf">
                <small class="text-muted d-block mt-1">
                    Photos (JPG, PNG, WEBP, HEIC) or PDFs · up to 10 files · 10 MB each.
                    Tip: take the photo straight on, in good light, with the whole table visible.
                </small>
                <ul class="small mt-2 mb-0 pl-3" id="fileList"></ul>
            </div>
        </div>

        <button class="btn btn-success btn-lg mb-4" id="readBtn" {{ $configured ? '' : 'disabled' }}>
            <i class="fas fa-magic mr-1"></i> Read Sheets
        </button>
        <span class="ml-2 text-muted d-none" id="readingNote">
            <i class="fas fa-spinner fa-spin mr-1"></i> Reading sheets… this can take up to a minute per file.
        </span>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var dept = document.getElementById('departmentSelect');
    var unit = document.getElementById('unitSelect');

    // Picking a lecturer fills in their department; the unit list follows the department.
    document.getElementById('lecturerSelect').addEventListener('change', function () {
        var d = this.selectedOptions[0] && this.selectedOptions[0].dataset.department;
        if (d) { dept.value = d; filterUnits(); }
    });

    function filterUnits() {
        Array.prototype.forEach.call(unit.options, function (o) {
            if (!o.value) return;
            var show = !dept.value || o.dataset.department === dept.value;
            o.hidden = !show;
            if (!show && o.selected) unit.value = '';
        });
    }
    dept.addEventListener('change', filterUnits);
    filterUnits();

    document.getElementById('filesInput').addEventListener('change', function () {
        document.getElementById('fileList').innerHTML = Array.prototype.map.call(this.files, function (f) {
            var li = document.createElement('li');
            li.textContent = f.name + ' (' + (f.size / 1048576).toFixed(1) + ' MB)';
            return li.outerHTML;
        }).join('');
    });

    document.getElementById('uploadForm').addEventListener('submit', function () {
        document.getElementById('readBtn').disabled = true;
        document.getElementById('readingNote').classList.remove('d-none');
    });
})();
</script>
@endpush
