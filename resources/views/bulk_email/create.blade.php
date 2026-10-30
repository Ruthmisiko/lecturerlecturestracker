@extends('layouts.app')

@php
    $statusOptions = \App\Services\LecturerPayroll::STATUSES;
    $checkedStatuses = $statuses ?? array_keys($statusOptions);
    $picked = collect(old('lecturer_ids', []))->map(fn ($id) => (int) $id)->all();
@endphp

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Bulk Email</h1>
            <small class="text-muted">Email many lecturers at once, with or without their payslips. Each email is filled in with that lecturer's own details.</small>
        </div>
        @if (Auth::user()->hasAccess('email-settings.manage'))
            <a href="{{ route('email-templates.index') }}" class="btn btn-outline-secondary"><i class="far fa-file-alt mr-1"></i> Email Templates</a>
        @endif
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    @include('adminlte-templates::common.errors')

    {{-- Period and payable statuses: used for payslips and for the pay/hours details --}}
    <form method="GET" class="card">
        <div class="card-body py-3 d-flex flex-wrap align-items-end" style="gap:12px;">
            <div>
                <label class="small text-muted mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small text-muted mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small text-muted mb-1 d-block">Payable hours with status</label>
                @foreach ($statusOptions as $key => $label)
                    <div class="form-check form-check-inline small">
                        <input class="form-check-input" type="checkbox" name="statuses[]" value="{{ $key }}" id="st_{{ $key }}" {{ in_array($key, $checkedStatuses) ? 'checked' : '' }}>
                        <label class="form-check-label" for="st_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            <button class="btn btn-sm btn-success">Apply</button>
            <span class="small text-muted">Period: <strong>{{ $periodText }}</strong></span>
        </div>
    </form>

    @if ($configs->isEmpty())
        <div class="alert alert-warning">
            No email account is set up yet. Add one under <a href="{{ route('email-configurations.index') }}">Email Settings</a>, then come back.
        </div>
    @else
    <form method="POST" action="{{ route('bulk-email.send') }}" id="bulkForm">
        @csrf
        @if ($from)<input type="hidden" name="from" value="{{ $from }}">@endif
        @if ($to)<input type="hidden" name="to" value="{{ $to }}">@endif
        @foreach ($statuses ?? [] as $s)<input type="hidden" name="statuses[]" value="{{ $s }}">@endforeach

        <div class="row">
            {{-- Recipients --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Lecturers</strong>
                        <span class="badge badge-success" id="pickedCount">0 selected</span>
                    </div>
                    <div class="card-body pb-2">
                        <input type="search" id="recipientSearch" class="form-control form-control-sm mb-2" placeholder="Search name or email…">
                        <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                            <select id="deptFilter" class="form-control form-control-sm" style="max-width:200px;">
                                <option value="">All departments</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" id="hoursOnly">
                                <label class="form-check-label" for="hoursOnly">Only with payable hours</label>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-xs btn-outline-success" id="selectShown">Select all shown</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="clearAll">Clear</button>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" style="max-height:520px; overflow-y:auto;" id="recipientList">
                        @forelse ($rows as $r)
                            <label class="list-group-item list-group-item-action d-flex align-items-center mb-0 recipient"
                                   data-search="{{ strtolower($r->name . ' ' . $r->email) }}" data-dept="{{ $r->department_id }}" data-hours="{{ $r->hours }}"
                                   style="cursor:{{ $r->email ? 'pointer' : 'not-allowed' }};">
                                <input type="checkbox" name="lecturer_ids[]" value="{{ $r->id }}" class="mr-3 pick"
                                       {{ $r->email ? '' : 'disabled' }} {{ in_array($r->id, $picked) ? 'checked' : '' }}>
                                <span class="flex-grow-1" style="min-width:0;">
                                    <span class="font-weight-bold d-block text-truncate">{{ $r->name }}</span>
                                    <small class="text-muted d-block text-truncate">
                                        {{ $r->department }} ·
                                        @if ($r->email) {{ $r->email }} @else <span class="text-danger">No email saved</span> @endif
                                    </small>
                                </span>
                                <small class="text-nowrap ml-2 {{ $r->hours > 0 ? '' : 'text-muted' }}">{{ rtrim(rtrim(number_format($r->hours, 2), '0'), '.') }} hrs</small>
                            </label>
                        @empty
                            <div class="list-group-item text-muted text-center p-4">No lecturers yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Message --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Send from</label>
                                <select name="email_configuration_id" class="form-control">
                                    @foreach ($configs as $cfg)
                                        <option value="{{ $cfg->id }}" {{ old('email_configuration_id') == $cfg->id || (!old('email_configuration_id') && $cfg->is_default) ? 'selected' : '' }}>
                                            {{ $cfg->name }} &lt;{{ $cfg->from_address }}&gt;
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bulkTemplate">Template</label>
                                <select id="bulkTemplate" class="form-control">
                                    <option value="">— None (write my own) —</option>
                                    @foreach ($templates as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->name }}{{ $tpl->is_default ? ' (default)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="d-block">What to send</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="attach_payslip" value="0" id="sendMessage" {{ old('attach_payslip') ? '' : 'checked' }}>
                                <label class="form-check-label" for="sendMessage">Message only</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="attach_payslip" value="1" id="sendPayslip" {{ old('attach_payslip') ? 'checked' : '' }}>
                                <label class="form-check-label" for="sendPayslip">Message + payslip PDF</label>
                            </div>
                            <small class="text-muted d-block" id="payslipNote">Each lecturer gets their own payslip for {{ $periodText }}. Lecturers with no payable hours in the period are skipped.</small>
                        </div>

                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" id="bulkSubject" class="form-control" required maxlength="200" value="{{ old('subject') }}" data-placeholder-target>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="body" id="bulkBody" class="form-control" rows="12" required maxlength="10000" data-placeholder-target>{{ old('body') }}</textarea>
                        </div>

                        <details class="mb-2" open>
                            <summary class="small font-weight-bold" style="cursor:pointer;"><i class="fas fa-plus-circle mr-1"></i> Insert lecturer details</summary>
                            <p class="small text-muted mt-2 mb-2">Click in the subject or message, then click a detail. It is replaced with each lecturer's own value when sending, e.g. <code>{name}</code> becomes their name.</p>
                            @include('email_templates._placeholders')
                        </details>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted">Lecturers without an email address can't be selected.</small>
                        <button type="submit" class="btn btn-success" id="bulkSend"><i class="far fa-paper-plane mr-1"></i> <span>Send</span></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('bulkForm');
    if (!form) return;

    var templates = @json($templates->keyBy('id'));
    var defaultTpl = @json($templates->firstWhere('is_default', true)?->id);
    var subject = document.getElementById('bulkSubject'), body = document.getElementById('bulkBody');
    var tplSelect = document.getElementById('bulkTemplate');
    var items = Array.prototype.slice.call(document.querySelectorAll('.recipient'));

    // Template: fill subject and message with its text (placeholders are filled per lecturer when sending).
    tplSelect.addEventListener('change', function () {
        var tpl = templates[this.value];
        if (!tpl) return;
        if ((subject.value || body.value) && !confirm('Replace the subject and message with this template?')) {
            this.value = '';
            return;
        }
        subject.value = tpl.subject;
        body.value = tpl.body;
    });
    if (defaultTpl && !subject.value && !body.value) {
        tplSelect.value = defaultTpl;
        subject.value = templates[defaultTpl].subject;
        body.value = templates[defaultTpl].body;
    }

    // Recipients: filters, select all, count.
    var search = document.getElementById('recipientSearch'), dept = document.getElementById('deptFilter'), hoursOnly = document.getElementById('hoursOnly');

    function filter() {
        var q = search.value.toLowerCase();
        items.forEach(function (el) {
            var show = el.dataset.search.indexOf(q) !== -1
                && (!dept.value || el.dataset.dept === dept.value)
                && (!hoursOnly.checked || parseFloat(el.dataset.hours) > 0);
            el.style.setProperty('display', show ? '' : 'none', 'important');
        });
    }

    function count() {
        var n = form.querySelectorAll('.pick:checked').length;
        document.getElementById('pickedCount').textContent = n + ' selected';
        document.querySelector('#bulkSend span').textContent = n ? 'Send to ' + n + ' lecturer' + (n === 1 ? '' : 's') : 'Send';
    }

    [search, dept, hoursOnly].forEach(function (el) { el.addEventListener('input', filter); el.addEventListener('change', filter); });
    form.addEventListener('change', function (e) { if (e.target.classList.contains('pick')) count(); });

    document.getElementById('selectShown').addEventListener('click', function () {
        items.forEach(function (el) {
            var box = el.querySelector('.pick');
            if (el.style.display !== 'none' && !box.disabled) box.checked = true;
        });
        count();
    });
    document.getElementById('clearAll').addEventListener('click', function () {
        form.querySelectorAll('.pick').forEach(function (box) { box.checked = false; });
        count();
    });

    // Payslip note only when attaching payslips.
    function payslipNote() {
        document.getElementById('payslipNote').style.display = document.getElementById('sendPayslip').checked ? '' : 'none';
    }
    form.querySelectorAll('input[name="attach_payslip"]').forEach(function (r) { r.addEventListener('change', payslipNote); });

    form.addEventListener('submit', function (e) {
        var n = form.querySelectorAll('.pick:checked').length;
        if (!n) { e.preventDefault(); alert('Tick at least one lecturer.'); return; }
        var what = document.getElementById('sendPayslip').checked ? 'this message and their payslips' : 'this message';
        if (!confirm('Send ' + what + ' to ' + n + ' lecturer' + (n === 1 ? '' : 's') + '?')) { e.preventDefault(); return; }
        var btn = document.getElementById('bulkSend');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending… please keep this page open';
    });

    count();
    payslipNote();
})();
</script>
@endpush
