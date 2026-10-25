@extends('layouts.app')

@push('styles')
<style>
    .pay-report { font-size: 13px; min-width: 1250px; }
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
            @foreach ($statuses ?? [] as $s)<input type="hidden" name="statuses[]" value="{{ $s }}">@endforeach
            <button class="btn btn-sm btn-success">Apply</button>
            <a href="{{ route('dashboard.report', request()->only('statuses')) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            <small class="text-muted ml-auto">Use <strong>Payable hrs</strong> under a lecturer's gross amount to choose which statuses count for that lecturer.</small>
        </div>
    </form>

    @forelse ($sections as $si => $section)
        <div class="card report-section" data-section="{{ $si }}">
            <div class="card-header">
                <p class="section-title">{{ $section->name }}</p>
            </div>
            <div class="card-body p-0 table-responsive" style="overflow: visible;">
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section->rows as $i => $r)
                            @php $on = $r->statuses ?: array_keys($statusOptions); @endphp
                            <tr data-lecturer="{{ $r->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $r->kra_pin }}</td>
                                <td><a href="{{ route('lecturers.show', $r->id) }}">{{ $r->name }}</a></td>
                                <td class="num c-hours" data-v="{{ $r->hours }}">{{ $n($r->hours) }}</td>
                                <td class="num">{{ $n($r->rate) }}</td>
                                <td class="num">
                                    <span class="c-gross" data-v="{{ $r->gross }}">{{ $n($r->gross) }}</span>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-outline-secondary payable-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-filter mr-1"></i>Payable hrs: <strong class="payable-label">{{ $r->statuses ? collect($r->statuses)->map(fn ($s) => $statusOptions[$s])->join(', ') : 'All' }}</strong>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right p-3 text-left payable-menu" onclick="event.stopPropagation()">
                                            <h6 class="dropdown-header px-0">Count hours with status</h6>
                                            @foreach ($statusOptions as $key => $label)
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input payable-status" type="checkbox" value="{{ $key }}" id="rs_{{ $r->id }}_{{ $key }}" {{ in_array($key, $on) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="rs_{{ $r->id }}_{{ $key }}">{{ $label }}</label>
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
                                <td><input type="text" class="form-control form-control-sm pv-input" maxlength="50" style="min-width:110px;" value="{{ $pv[$r->id] ?? '' }}" placeholder="e.g. M156028"></td>
                                <td class="text-center">
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
                            <td colspan="4"></td>
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
            if (v) p.append('pv[' + tr.dataset.lecturer + ']', v);
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

            tr.classList.add('row-busy');
            fetch(rowUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                .then(function (row) {
                    ['hours', 'gross', 'housing', 'tax', 'net'].forEach(function (k) { setCell(tr, 'c-' + k, row[k]); });
                    tr.querySelector('.c-period').textContent = row.period_taught || '';
                    refreshTotals();
                })
                .catch(function () { alert('Could not recalculate this lecturer. Please try again.'); })
                .finally(function () { tr.classList.remove('row-busy'); });
        });
    });

    // Typed PV numbers go into the Excel/PDF links.
    document.querySelectorAll('.pv-input').forEach(function (input) { input.addEventListener('input', refreshTotals); });

    refreshTotals();
})();
</script>
@endpush
