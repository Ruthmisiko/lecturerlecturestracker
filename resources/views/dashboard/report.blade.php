@extends('layouts.app')

@push('styles')
<style>
    .pay-report { font-size: 13px; min-width: 1500px; }
    tr.ref-dirty .save-row { background: #ffc107; border-color: #ffc107; color: #212529; }
    .pay-report th { white-space: nowrap; font-size: 12px; text-transform: uppercase; vertical-align: bottom; }
    .pay-report td { vertical-align: middle; padding: 6px 8px; }
    .pay-report .num { text-align: right; white-space: nowrap; }
    .pay-report tfoot td { font-weight: 700; background: var(--primary-soft); }
    .section-title { font-weight: 700; font-size: 16px; text-transform: uppercase; margin: 0; }
    .ahl-summary { width: auto; margin: 10px 0 0; font-size: 13px; }
    .ahl-summary td { padding: 3px 12px; background: #e2e3e5; font-weight: 700; border: 1px solid #fff; }
    .ahl-summary td.num { background: #f1f2f3; min-width: 140px; }
    .payable-btn { font-size: 11px; padding: 0 6px; margin-top: 3px; white-space: nowrap; }
    .payable-menu { min-width: 260px; }
    tr.row-busy td { opacity: .5; }
    /* Wide table scrolls inside its card instead of stretching the page */
    .report-scroll { overflow-x: auto; max-width: 100%; }
    .report-section { max-width: 100%; }
    .content-wrapper { overflow-x: hidden; }
</style>
@endpush

@section('content')
@php
    $n   = fn ($v) => number_format($v, 2);
    $rs  = collect($overrides)->map(fn ($s) => $s ? implode(',', $s) : 'all')->all();
    $statusOptions = \App\Services\LecturerPayroll::STATUSES;
    $base = array_filter(request()->only('from', 'to', 'department_id', 'statuses'));
@endphp

<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Lecturer Payment Report</h1>
            <small class="text-muted">
                Period: {{ !$from && !$to ? 'All time' : (($from ?: 'Start') . ' → ' . ($to ?: 'Today')) }}
                · {{ $department->name ?? 'All departments' }} · Amounts in KES
            </small>
        </div>
        <div>
            <a href="{{ route('dashboard', request()->only('from', 'to', 'statuses')) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard
            </a>
            <button type="button" id="saveAllRefs" class="btn btn-primary" title="Save the PV / VCH numbers and transaction refs of every lecturer shown"><i class="fas fa-save mr-1"></i> Save all</button>
            <a href="#" data-format="excel" class="btn btn-success export-link"><i class="fas fa-file-excel mr-1"></i> Excel</a>
            <a href="#" data-format="pdf" class="btn btn-danger export-link"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
        </div>
    </div>
</section>

<div class="content px-3">
    <form method="GET" class="card mb-3">
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
                <label class="small text-muted mb-1">Department</label>
                <select name="department_id" class="form-control form-control-sm" style="min-width: 200px;">
                    <option value="">All departments</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" {{ ($department->id ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            @foreach ($statuses ?? ["all"] as $s)<input type="hidden" name="statuses[]" value="{{ $s }}">@endforeach
            <button class="btn btn-sm btn-success">Apply</button>
            <a href="{{ route('dashboard.report', request()->only('statuses')) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            <small class="text-muted ml-auto">Use <strong>Payable hrs</strong> under a lecturer's gross amount to choose which statuses count for that lecturer.</small>
        </div>
    </form>

    @forelse ($sections as $si => $section)
        <div class="card report-section" data-section="{{ $si }}">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
                <p class="section-title">{{ $section->name }}</p>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary clear-refs" title="Empty every transaction ref in this department">Clear refs</button>
                    <button type="button" class="btn btn-sm btn-primary save-section"><i class="fas fa-save mr-1"></i> Save department</button>
                </div>
            </div>
            <div class="card-body p-0 report-scroll">
                <table class="table table-bordered table-striped mb-0 pay-report">
                    <thead class="thead-light">
                        <tr>
                            <th>SNO</th>
                            <th>Employee PIN</th>
                            <th>Name</th>
                            <th class="num">Hours Worked</th>
                            <th class="num">Rate</th>
                            <th class="num">Gross Amount</th>
                            <th class="num">House Levy</th>
                            <th class="num">Tax Deducted</th>
                            <th class="num">Net Amount</th>
                            <th>Department</th>
                            <th>Period Taught</th>
                            <th>PV No / VCH No</th>
                            <th>Transaction Ref</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section->rows as $i => $r)
                            @php $on = $r->statuses ?: array_keys($statusOptions); @endphp
                            <tr data-lecturer="{{ $r->id }}" data-dept="{{ $r->department_key ?? '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $r->kra_pin }}</td>
                                <td><a href="{{ route('lecturers.show', $r->id) }}">{{ $r->name }}</a></td>
                                <td class="num"><span class="c-hours" data-v="{{ $r->hours }}">{{ $n($r->hours) }}</span><div class="c-capped small text-danger" title="Hours above a unit's max hours are not paid">{{ $r->capped_hours > 0 ? 'capped: ' . $n($r->capped_hours) . ' over max' : '' }}</div></td>
                                <td class="num">{{ $n($r->rate) }}</td>
                                <td class="num">
                                    <span class="c-gross" data-v="{{ $r->gross }}">{{ $n($r->gross) }}</span>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-outline-secondary payable-btn dropdown-toggle" data-toggle="dropdown" data-display="static" aria-expanded="false">
                                            <i class="fas fa-filter mr-1"></i>Payable hrs: <strong class="payable-label">{{ $r->statuses ? collect($r->statuses)->map(fn ($s) => $statusOptions[$s])->join(', ') : 'All' }}</strong>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right p-3 text-left payable-menu" onclick="event.stopPropagation()">
                                            <h6 class="dropdown-header px-0">Count hours with status</h6>
                                            @foreach ($statusOptions as $key => $label)
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input payable-status" type="checkbox" value="{{ $key }}" id="rs_{{ $r->id }}_{{ $r->department_key ?? 0 }}_{{ $key }}" {{ in_array($key, $on) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="rs_{{ $r->id }}_{{ $r->department_key ?? 0 }}_{{ $key }}">{{ $label }}</label>
                                                </div>
                                            @endforeach
                                            <div class="d-flex justify-content-between mt-2">
                                                <button type="button" class="btn btn-sm btn-link px-0 payable-all">Select all</button>
                                                <button type="button" class="btn btn-sm btn-success payable-apply">Apply</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="num text-danger c-housing" data-v="{{ $r->housing }}">{{ $n($r->housing) }}</td>
                                <td class="num text-danger c-tax" data-v="{{ $r->tax }}">{{ $n($r->tax) }}</td>
                                <td class="num font-weight-bold c-net" data-v="{{ $r->net }}">{{ $n($r->net) }}</td>
                                <td>{{ $r->department }}</td>
                                <td class="c-period">{{ $r->period_taught }}</td>
                                <td><input type="text" class="form-control form-control-sm pv-input" maxlength="50" style="min-width:110px;" value="{{ $pv[$r->id . '_' . ($r->department_key ?? 0)] ?? '' }}" placeholder="e.g. M156028"></td>
                                <td><input type="text" class="form-control form-control-sm ref-input" maxlength="100" style="min-width:140px;" value="{{ $refs[$r->id . '_' . ($r->department_key ?? 0)] ?? '' }}" placeholder="e.g. QJ12ABC345" data-manual="{{ ($refs[$r->id . '_' . ($r->department_key ?? 0)] ?? '') !== '' ? 1 : 0 }}"></td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-xs btn-outline-primary save-row" title="Save this line"><i class="fas fa-save"></i></button>
                                    <a href="#" class="btn btn-xs btn-outline-success payslip-link" title="Print payslip"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">TOTAL</td>
                            <td class="num t-hours"></td>
                            <td></td>
                            <td class="num t-gross"></td>
                            <td class="num t-housing"></td>
                            <td class="num t-tax"></td>
                            <td class="num t-net"></td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer bg-white">
                <table class="ahl-summary">
                    <tr><td>EMPLOYEE AHL</td><td class="num s-employee"></td></tr>
                    <tr><td>EMPLOYER AHL</td><td class="num s-employer"></td></tr>
                    <tr><td>TOTAL AHL</td><td class="num s-total"></td></tr>
                    <tr><td>TAX DEDUCTED</td><td class="num s-tax"></td></tr>
                </table>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted p-5">No lectures were recorded for this period.</div></div>
    @endforelse

    @if ($sections->count() > 1)
        <div class="card">
            <div class="card-body d-flex flex-wrap" style="gap: 24px; font-weight: 700;">
                <span>GRAND TOTAL — Hours: <span class="g-hours"></span></span>
                <span>Gross: <span class="g-gross"></span></span>
                <span class="text-danger">House levy: <span class="g-housing"></span></span>
                <span class="text-danger">Tax: <span class="g-tax"></span></span>
                <span>Net: <span class="g-net"></span></span>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var STATUSES = @json($statusOptions);
    var ALL      = Object.keys(STATUSES);
    var baseQuery = @json($base);
    var rs        = @json((object) $rs);   // lecturer id => "ok,own_clash" | "all"
    var reportUrl = @json(route('dashboard.report'));
    var rowUrl    = @json(route('dashboard.report.row'));
    var payslipUrl = @json(route('dashboard.payslip', ['lecturer' => '__ID__']));

    function fmt(v) { return Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function sum(nodes) { return Array.prototype.reduce.call(nodes, function (t, el) { return t + parseFloat(el.dataset.v || 0); }, 0); }

    function query(extra) {
        var p = new URLSearchParams();
        Object.keys(baseQuery).forEach(function (k) {
            var v = baseQuery[k];
            if (Array.isArray(v)) v.forEach(function (x) { p.append(k + '[]', x); }); else if (v !== null && v !== '') p.append(k, v);
        });
        Object.keys(rs).forEach(function (id) { p.append('rs[' + id + ']', rs[id]); });
        document.querySelectorAll('tr[data-lecturer]').forEach(function (tr) {
            var v = tr.querySelector('.pv-input').value.trim();
            var k = tr.dataset.lecturer + '_' + (tr.dataset.dept || 0);
            if (v) p.append('pv[' + k + ']', v);
            var ref = tr.querySelector('.ref-input').value.trim();
            if (ref) p.append('ref[' + k + ']', ref);
        });
        Object.keys(extra || {}).forEach(function (k) { p.append(k, extra[k]); });
        return p.toString();
    }

    function rowStatuses(tr) {
        return Array.prototype.map.call(tr.querySelectorAll('.payable-status:checked'), function (b) { return b.value; });
    }
    function statusText(list) { return list.length === ALL.length ? 'All' : list.map(function (s) { return STATUSES[s]; }).join(', '); }

    function refreshTotals() {
        var grand = { hours: 0, gross: 0, housing: 0, tax: 0, net: 0 };
        document.querySelectorAll('.report-section').forEach(function (sec) {
            var t = {};
            ['hours', 'gross', 'housing', 'tax', 'net'].forEach(function (k) {
                t[k] = sum(sec.querySelectorAll('.c-' + k));
                sec.querySelector('.t-' + k).textContent = fmt(t[k]);
                grand[k] += t[k];
            });
            sec.querySelector('.s-employee').textContent = fmt(t.housing);
            sec.querySelector('.s-employer').textContent = fmt(t.housing);
            sec.querySelector('.s-total').textContent = fmt(t.housing * 2);
            sec.querySelector('.s-tax').textContent = fmt(t.tax);
        });
        Object.keys(grand).forEach(function (k) {
            var el = document.querySelector('.g-' + k);
            if (el) el.textContent = fmt(grand[k]);
        });

        // Downloads and payslips follow each row's payable statuses.
        document.querySelectorAll('.export-link').forEach(function (a) { a.href = reportUrl + '?' + query({ format: a.dataset.format }); });
        document.querySelectorAll('tr[data-lecturer]').forEach(function (tr) {
            var p = new URLSearchParams();
            if (baseQuery.from) p.append('from', baseQuery.from);
            if (baseQuery.to) p.append('to', baseQuery.to);
            var list = rowStatuses(tr);
            if (list.length !== ALL.length) list.forEach(function (s) { p.append('statuses[]', s); });
            if (tr.dataset.dept) p.append('department', tr.dataset.dept);
            tr.querySelector('.payslip-link').href = payslipUrl.replace('__ID__', tr.dataset.lecturer) + '?' + p.toString();
        });
    }

    function setCell(tr, cls, value) {
        var el = tr.querySelector('.' + cls);
        el.dataset.v = value;
        el.textContent = fmt(value);
    }

    document.querySelectorAll('tr[data-lecturer]').forEach(function (tr) {
        tr.querySelector('.payable-all').addEventListener('click', function () {
            tr.querySelectorAll('.payable-status').forEach(function (b) { b.checked = true; });
        });

        tr.querySelector('.payable-apply').addEventListener('click', function () {
            var list = rowStatuses(tr);
            if (!list.length) { alert('Tick at least one status.'); return; }
            var id = tr.dataset.lecturer;
            rs[id] = list.length === ALL.length ? 'all' : list.join(',');
            tr.querySelector('.payable-label').textContent = statusText(list);
            try { $(tr).find('[data-toggle="dropdown"]').dropdown('hide'); } catch (e) {}

            var p = new URLSearchParams({ lecturer: id });
            if (baseQuery.from) p.append('from', baseQuery.from);
            if (baseQuery.to) p.append('to', baseQuery.to);
            if (list.length !== ALL.length) list.forEach(function (s) { p.append('statuses[]', s); });

            // The choice applies to the lecturer, so every department line of that lecturer is updated.
            var siblings = Array.prototype.slice.call(document.querySelectorAll('tr[data-lecturer="' + id + '"]'));
            siblings.forEach(function (t) {
                t.classList.add('row-busy');
                t.querySelectorAll('.payable-status').forEach(function (b) { b.checked = list.indexOf(b.value) !== -1; });
                t.querySelector('.payable-label').textContent = statusText(list);
            });
            fetch(rowUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                .then(function (row) {
                    siblings.forEach(function (t) {
                        var part = (row.departments || []).filter(function (d) { return String(d.department_key) === t.dataset.dept; })[0] || row;
                        ['hours', 'gross', 'housing', 'tax', 'net'].forEach(function (k) { setCell(t, 'c-' + k, part[k]); });
                        t.querySelector('.c-period').textContent = part.period_taught || '';
                        t.querySelector('.c-capped').textContent = part.capped_hours > 0 ? 'capped: ' + fmt(part.capped_hours) + ' over max' : '';
                    });
                    refreshTotals();
                })
                .catch(function () { alert('Could not recalculate this lecturer. Please try again.'); })
                .finally(function () { siblings.forEach(function (t) { t.classList.remove('row-busy'); }); });
        });
    });

    // Typed PV numbers and transaction refs go into the Excel/PDF links and mark the line as unsaved.
    function markDirty(tr) { tr.classList.add('ref-dirty'); }
    document.querySelectorAll('.pv-input').forEach(function (input) {
        input.addEventListener('input', function () { markDirty(input.closest('tr')); refreshTotals(); });
    });

    // A transaction ref typed on one line fills the other lines of that department that the user has not touched.
    document.querySelectorAll('.ref-input').forEach(function (input) {
        input.addEventListener('input', function () {
            input.dataset.manual = '1';
            markDirty(input.closest('tr'));
            input.closest('.report-section').querySelectorAll('.ref-input').forEach(function (other) {
                if (other !== input && other.dataset.manual !== '1') {
                    other.value = input.value;
                    markDirty(other.closest('tr'));
                }
            });
            refreshTotals();
        });
    });

    document.querySelectorAll('.clear-refs').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.report-section').querySelectorAll('.ref-input').forEach(function (i) {
                i.value = '';
                i.dataset.manual = '1';
                markDirty(i.closest('tr'));
            });
            refreshTotals();
        });
    });

    // Saving: one line, a whole department, or everything shown.
    var saveUrl = @json(route('dashboard.report.references'));
    var csrf    = @json(csrf_token());

    function saveRows(trs, button) {
        trs = Array.prototype.slice.call(trs);
        if (!trs.length) return;
        var items = trs.map(function (tr) {
            return {
                lecturer_id: tr.dataset.lecturer,
                department_key: tr.dataset.dept || 0,
                pv_no: tr.querySelector('.pv-input').value.trim(),
                transaction_ref: tr.querySelector('.ref-input').value.trim()
            };
        });
        var label = button.innerHTML;
        button.disabled = true;
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ from: baseQuery.from || null, to: baseQuery.to || null, items: items })
        })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function () {
                trs.forEach(function (tr) {
                    tr.classList.remove('ref-dirty');
                    var ref = tr.querySelector('.ref-input');
                    if (ref.value.trim() !== '') ref.dataset.manual = '1';
                });
                button.innerHTML = '<i class="fas fa-check mr-1"></i> Saved';
                setTimeout(function () { button.innerHTML = label; }, 1500);
            })
            .catch(function () { alert('Could not save. Please try again.'); button.innerHTML = label; })
            .finally(function () { button.disabled = false; });
    }

    document.querySelectorAll('.save-row').forEach(function (b) {
        b.addEventListener('click', function () { saveRows([b.closest('tr')], b); });
    });
    document.querySelectorAll('.save-section').forEach(function (b) {
        b.addEventListener('click', function () { saveRows(b.closest('.report-section').querySelectorAll('tr[data-lecturer]'), b); });
    });
    document.getElementById('saveAllRefs').addEventListener('click', function () {
        saveRows(document.querySelectorAll('tr[data-lecturer]'), this);
    });

    // The table scrolls inside its card, so open Payable hrs menus as fixed overlays (not clipped).
    if (window.jQuery && jQuery.fn.on) $(document).on('shown.bs.dropdown', '.report-section .dropdown', function () {
        var btn = this.querySelector('.payable-btn').getBoundingClientRect();
        var menu = this.querySelector('.payable-menu');
        menu.style.position = 'fixed';
        menu.style.top = (btn.bottom + 4) + 'px';
        menu.style.left = Math.max(8, btn.right - menu.offsetWidth) + 'px';
        menu.style.right = 'auto';
    });
    // Close an open menu when the page or table scrolls, so it never floats away from its row.
    var closeMenus = function () { if (window.jQuery && jQuery.fn.dropdown) $('.report-section .dropdown.show [data-toggle="dropdown"]').dropdown('hide'); };
    window.addEventListener('scroll', closeMenus, true);

    refreshTotals();
})();
</script>
@endpush
