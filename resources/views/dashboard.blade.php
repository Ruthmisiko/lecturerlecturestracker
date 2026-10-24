@extends('layouts.app')

@push('styles')
<style>
    .dash-stat { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .dash-stat .card-body { display: flex; align-items: center; gap: 16px; padding: 20px; }
    .dash-stat .icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .dash-stat .label { font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; margin: 0; }
    .dash-stat .value { font-size: 26px; font-weight: 700; margin: 0; line-height: 1.2; color: #212529; }
    .money-card { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .money-card .card-body { padding: 14px 18px; }
    .money-card .label { font-size: 12px; color: #6c757d; text-transform: uppercase; margin: 0; }
    .money-card .value { font-size: 18px; font-weight: 700; margin: 0; }
    .panel { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .panel .card-header { background: #fff; border-bottom: 1px solid #eef0f2; font-weight: 600; }
    .lecturer-list { max-height: 460px; overflow-y: auto; }
    .lecturer-item { cursor: pointer; border-left: 3px solid transparent; color: #212529; }
    .lecturer-item:hover { background: #f4f9f5; }
    .lecturer-item.selected { background: var(--primary-soft); border-left-color: var(--primary); }
    .lecturer-item.selected .name { color: var(--primary-dark); }
    .lecturer-item .dept { font-size: 12px; color: #6c757d; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; }
    .detail-row:last-child { border-bottom: 0; }
    .detail-row.net { font-size: 18px; font-weight: 700; color: var(--primary-dark); border-top: 2px solid var(--primary); border-bottom: 0; margin-top: 6px; }
    .deduction { color: #c0392b; }
    .bar { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; }
    .bar > span { display: block; height: 100%; background: var(--primary); }
    .empty-detail { color: #6c757d; text-align: center; padding: 60px 20px; }
    .narration-box { background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; }
    .narration-text { white-space: pre-line; font-size: 14px; line-height: 1.5; color: #333; }
</style>
@endpush

@section('content')
@php
    $money = fn ($v) => 'KES ' . number_format($v, 4);
    $maxDeptHours = max(1, $departmentSummary->max('hours') ?? 0);
    $keep = array_filter(['statuses' => $statuses]);
    $statusOptions = \App\Services\LecturerPayroll::STATUSES;
    $checked = $statuses ?? array_keys($statusOptions);
@endphp

<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Dashboard</h1>
            <small class="text-muted">Welcome back, {{ Auth::user()->name }}</small>
        </div>
        <a href="{{ route('dashboard.report', request()->only('from', 'to', 'statuses')) }}" class="btn btn-success">
            <i class="fas fa-file-invoice-dollar mr-1"></i> Payment Report
        </a>
    </div>
</section>

<div class="content px-3">

    {{-- Period filter --}}
    <form method="GET" class="card panel mb-3">
        <div class="card-body py-3 d-flex flex-wrap align-items-end" style="gap:12px;">
            <div>
                <label class="small text-muted mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small text-muted mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            @foreach ($statuses ?? [] as $s)<input type="hidden" name="statuses[]" value="{{ $s }}">@endforeach
            <button class="btn btn-sm btn-success">Apply</button>
            <div class="btn-group btn-group-sm ml-md-2">
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()] + $keep) }}">This month</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString()] + $keep) }}">Last month</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()] + $keep) }}">This year</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', $keep) }}">All time</a>
            </div>
            <span class="ml-auto small text-muted">
                Showing: <strong>{{ !$from && !$to ? 'All time' : (($from ?: 'Start') . ' → ' . ($to ?: 'Today')) }}</strong>
            </span>
        </div>
    </form>

    {{-- Headline stats --}}
    <div class="row">
        @foreach ([
            ['Total Lecturers', $stats['lecturers'], 'fa-chalkboard-teacher', 'var(--primary-soft)', 'var(--primary)'],
            ['Total Units', $stats['units'], 'fa-book', '#e3f2fd', '#1e88e5'],
            ['Total Departments', $stats['departments'], 'fa-building', '#fff3e0', '#fb8c00'],
            ['Hours Administered', number_format($stats['hours'], 2), 'fa-clock', '#f3e5f5', '#8e24aa'],
        ] as [$label, $value, $icon, $bg, $fg])
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card dash-stat h-100">
                    <div class="card-body">
                        <div class="icon" style="background:{{ $bg }};color:{{ $fg }};"><i class="fas {{ $icon }}"></i></div>
                        <div>
                            <p class="label">{{ $label }}</p>
                            <p class="value">{{ $value }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Payroll totals --}}
    @php
        // Show the rate in the label only when every paid lecturer uses the same one.
        $paidRows   = $rows->filter(fn ($r) => $r->gross > 0);
        $rateLabel  = fn ($field) => ($rates = $paidRows->pluck($field)->unique())->count() === 1
            ? ' (' . rtrim(rtrim(number_format($rates->first(), 2), '0'), '.') . '%)' : '';
    @endphp
    <div class="row">
        @foreach ([
            ['Total Gross Amount', $totals->gross, '', 'fa-money-bill-wave'],
            ['Total Tax (PAYE)' . $rateLabel('tax_rate'), $totals->tax, 'deduction', 'fa-receipt'],
            ['Housing Levy' . $rateLabel('housing_levy_rate'), $totals->housing, 'deduction', 'fa-home'],
            ['Employer Levy' . $rateLabel('housing_levy_rate'), $totals->employer_levy, '', 'fa-building'],
        ] as [$label, $value, $class, $icon])
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card money-card h-100">
                    <div class="card-body">
                        <p class="label"><i class="fas {{ $icon }} mr-1"></i> {{ $label }}</p>
                        <p class="value {{ $class }}">{{ $money($value) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($missingRate->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start">
            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
            <div>
                <strong>{{ $missingRate->count() }} lecturer(s) have hours but no rate per hour set</strong>, so their pay shows as 0:
                {{ $missingRate->pluck('name')->join(', ') }}.
                Set the rate from <a href="{{ route('lecturers.index') }}">Lecturers → Edit</a>.
            </div>
        </div>
    @endif

    {{-- Lecturer list + detail --}}
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users mr-1 text-success"></i> Lecturers</span>
                    <span class="badge badge-light">{{ $rows->count() }}</span>
                </div>
                <div class="p-2 border-bottom">
                    <input type="search" id="lecturerSearch" class="form-control form-control-sm" placeholder="Search lecturer or department…">
                </div>
                <div class="list-group list-group-flush lecturer-list" id="lecturerList">
                    @forelse ($rows as $row)
                        <div class="list-group-item lecturer-item d-flex justify-content-between align-items-center"
                             data-id="{{ $row->id }}" data-search="{{ strtolower($row->name . ' ' . $row->department) }}">
                            <div>
                                <div class="font-weight-bold name">{{ $row->name }}</div>
                                <div class="dept">{{ $row->department }}</div>
                            </div>
                            <span class="badge badge-pill {{ $row->hours > 0 ? 'badge-success' : 'badge-secondary' }}">{{ number_format($row->hours, 2) }} hrs</span>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">No lecturers yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card panel h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
                    <span><i class="fas fa-file-invoice mr-1 text-success"></i> Payment Summary</span>

                    {{-- Which clash statuses count as payable hours --}}
                    <form method="GET" class="dropdown" id="payableForm">
                        @if ($from)<input type="hidden" name="from" value="{{ $from }}">@endif
                        @if ($to)<input type="hidden" name="to" value="{{ $to }}">@endif
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter mr-1"></i> Payable hrs:
                            <strong>{{ $statuses ? collect($statuses)->map(fn ($s) => $statusOptions[$s])->join(', ') : 'All' }}</strong>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3" style="min-width: 270px;" onclick="event.stopPropagation()">
                            <h6 class="dropdown-header px-0">Count hours with status</h6>
                            @foreach ($statusOptions as $key => $label)
                                <div class="form-check mb-1">
                                    <input class="form-check-input payable-status" type="checkbox" name="statuses[]" value="{{ $key }}"
                                           id="payable_{{ $key }}" {{ in_array($key, $checked) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="payable_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between mt-2">
                                <button type="button" class="btn btn-sm btn-link px-0" id="payableAll">Select all</button>
                                <button type="submit" class="btn btn-sm btn-success">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body" id="lecturerDetail">
                    <div class="empty-detail">
                        <i class="fas fa-hand-pointer fa-2x mb-2"></i>
                        <p class="mb-0">Select a lecturer on the left to see their hours and pay.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Department breakdown --}}
    @if ($departmentSummary->isNotEmpty())
        <div class="card panel mb-4">
            <div class="card-header"><i class="fas fa-chart-bar mr-1 text-success"></i> Hours &amp; Net Pay by Department</div>
            <div class="card-body">
                @foreach ($departmentSummary as $dept)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <strong>{{ $dept->name }}</strong>
                            <span>{{ number_format($dept->hours, 2) }} hrs · {{ $money($dept->net) }}</span>
                        </div>
                        <div class="bar"><span style="width: {{ round($dept->hours / $maxDeptHours * 100) }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var rows = @json($rows->keyBy('id'));
    @php $payslipUrl = route('dashboard.payslip', array_merge(['lecturer' => '__ID__'], request()->only('from', 'to', 'statuses'))); @endphp
    var payslipUrl = @json($payslipUrl);
    var fmt = new Intl.NumberFormat('en-KE', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    var money = function (v) { return 'KES ' + fmt.format(v); };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var detail = document.getElementById('lecturerDetail');

    function line(label, value, cls) {
        return '<div class="detail-row ' + (cls || '') + '"><span>' + label + '</span><span>' + value + '</span></div>';
    }

    // Plain numbers for the narration: trim trailing zeros (23, 2.1833, 1,000) / money to 2 decimals.
    var num = function (v, maxDp) {
        return new Intl.NumberFormat('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: maxDp }).format(v);
    };
    var ksh = function (v) {
        return 'KSh ' + new Intl.NumberFormat('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
    };

    // Payment voucher narration for the selected lecturer.
    function narration(r) {
        var dept  = r.department_description ? 'the Department of ' + r.department_description : 'the department';
        var hours = num(r.hours, 4) + ' hour' + (r.hours == 1 ? '' : 's');
        var rate  = 'KSh ' + num(r.rate, 2);

        return 'Being payment for teaching services rendered in ' + dept + ' for ' + hours + ' at ' + rate +
            ' per hour, as per the attached supporting documentation.\n\n' +
            'Gross Amount: ' + ksh(r.gross) + ' (' + hours + ' × ' + rate + ')\n\n' +
            'PAYE (' + num(r.tax_rate, 2) + '%): ' + ksh(r.tax) + '\n\n' +
            'Affordable Housing Levy (AHL) (' + num(r.housing_levy_rate, 2) + '%): ' + ksh(r.housing) + '\n\n' +
            'Net Payable: ' + ksh(r.net);
    }

    function copyText(text, button) {
        var done = function () {
            button.innerHTML = '<i class="fas fa-check mr-1"></i>Copied';
            setTimeout(function () { button.innerHTML = '<i class="far fa-copy mr-1"></i>Copy'; }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
            return;
        }
        // Fallback for non-HTTPS pages.
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { alert('Press Ctrl+C to copy.'); }
        document.body.removeChild(ta);
    }

    detail.addEventListener('click', function (e) {
        var btn = e.target.closest('#copyNarration');
        if (btn) copyText(document.getElementById('narrationText').textContent, btn);
    });

    function show(id) {
        var r = rows[id];
        if (!r) return;

        var units = Object.keys(r.units).map(function (u) {
            return '<li class="d-flex justify-content-between"><span>' + esc(u) + '</span><span>' + r.units[u].toFixed(4) + ' hrs</span></li>';
        }).join('');

        detail.innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-3">' +
                '<div><h4 class="mb-0 font-weight-bold">' + esc(r.name) + '</h4><span class="text-muted">' + esc(r.department) + '</span></div>' +
                '<div class="text-nowrap">' +
                    '<a href="{{ url('lecturers') }}/' + r.id + '" class="btn btn-sm btn-outline-success mr-1">View lectures</a>' +
                    '<a href="' + payslipUrl.replace('__ID__', r.id) + '" class="btn btn-sm btn-success"><i class="fas fa-print mr-1"></i>Print Payslip</a>' +
                '</div>' +
            '</div>' +
            (r.rate <= 0 && r.hours > 0 ? '<div class="alert alert-warning py-2 small">No rate per hour set for this lecturer.</div>' : '') +
            line('KRA PIN', esc(r.kra_pin)) +
            (r.excluded_sessions > 0
                ? '<div class="alert alert-light border small py-2 my-2"><i class="fas fa-filter mr-1"></i>' +
                  r.excluded_sessions + ' session(s) / ' + r.excluded_hours.toFixed(4) + ' hrs not counted (status filter).</div>'
                : '') +
            line('Lecture sessions', r.sessions) +
            line('Total hours', r.hours.toFixed(4) + ' hrs') +
            line('Rate per hour', money(r.rate)) +
            line('<strong>Gross amount</strong> <small class="text-muted">(hours × rate)</small>', '<strong>' + money(r.gross) + '</strong>') +
            line('Tax (' + r.tax_rate + '%)', '− ' + money(r.tax), 'deduction') +
            line('Housing levy (' + r.housing_levy_rate + '%)', '− ' + money(r.housing), 'deduction') +
            line('Net pay', money(r.net), 'net') +
            (r.gross > 0
                ? '<div class="narration-box mt-3">' +
                      '<div class="d-flex justify-content-between align-items-center mb-2">' +
                          '<strong><i class="fas fa-align-left mr-1 text-success"></i> Narration</strong>' +
                          '<button type="button" class="btn btn-sm btn-outline-success" id="copyNarration"><i class="far fa-copy mr-1"></i>Copy</button>' +
                      '</div>' +
                      '<div class="narration-text" id="narrationText">' + esc(narration(r)) + '</div>' +
                  '</div>'
                : '') +
            (units ? '<h6 class="mt-4 font-weight-bold">Hours by unit</h6><ul class="list-unstyled small mb-0">' + units + '</ul>' : '') +
            (r.last_lecture ? '<p class="small text-muted mt-3 mb-0">Last lecture: ' + esc(r.last_lecture) + '</p>' : '');

        document.querySelectorAll('.lecturer-item').forEach(function (el) {
            el.classList.toggle('selected', el.dataset.id == id);
        });
    }

    document.querySelectorAll('.lecturer-item').forEach(function (el) {
        el.addEventListener('click', function () { show(el.dataset.id); });
    });

    document.getElementById('lecturerSearch').addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.lecturer-item').forEach(function (el) {
            el.style.setProperty('display', el.dataset.search.indexOf(q) !== -1 ? '' : 'none', 'important');
        });
    });

    // Re-open the lecturer that was selected before the page reloaded (e.g. after changing filters).
    var remembered = null;
    try { remembered = sessionStorage.getItem('dashLecturer'); } catch (e) {}
    var start = (remembered && rows[remembered]) ? remembered : (document.querySelector('.lecturer-item') || {dataset: {}}).dataset.id;
    if (start) show(start);

    document.querySelectorAll('.lecturer-item').forEach(function (el) {
        el.addEventListener('click', function () {
            try { sessionStorage.setItem('dashLecturer', el.dataset.id); } catch (e) {}
        });
    });

    document.getElementById('payableAll').addEventListener('click', function () {
        document.querySelectorAll('.payable-status').forEach(function (cb) { cb.checked = true; });
    });

    document.getElementById('payableForm').addEventListener('submit', function (e) {
        if (!document.querySelector('.payable-status:checked')) {
            e.preventDefault();
            alert('Tick at least one status.');
        }
    });
})();
</script>
@endpush
