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
    .lecturer-item.selected { background: #e9f6ec; border-left-color: #28a745; }
    .lecturer-item.selected .name { color: #1e7e34; }
    .lecturer-item .dept { font-size: 12px; color: #6c757d; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; }
    .detail-row:last-child { border-bottom: 0; }
    .detail-row.net { font-size: 18px; font-weight: 700; color: #1e7e34; border-top: 2px solid #28a745; border-bottom: 0; margin-top: 6px; }
    .deduction { color: #c0392b; }
    .bar { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; }
    .bar > span { display: block; height: 100%; background: #28a745; }
    .empty-detail { color: #6c757d; text-align: center; padding: 60px 20px; }
</style>
@endpush

@section('content')
@php
    $money = fn ($v) => 'KES ' . number_format($v, 2);
    $maxDeptHours = max(1, $departmentSummary->max('hours') ?? 0);
@endphp

<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Dashboard</h1>
            <small class="text-muted">Welcome back, {{ Auth::user()->name }}</small>
        </div>
        <a href="{{ route('dashboard.report', request()->only('from', 'to')) }}" class="btn btn-success">
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
            <button class="btn btn-sm btn-success">Apply</button>
            <div class="btn-group btn-group-sm ml-md-2">
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]) }}">This month</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString()]) }}">Last month</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()]) }}">This year</a>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">All time</a>
            </div>
            <span class="ml-auto small text-muted">
                Showing: <strong>{{ !$from && !$to ? 'All time' : (($from ?: 'Start') . ' → ' . ($to ?: 'Today')) }}</strong>
            </span>
        </div>
    </form>

    {{-- Headline stats --}}
    <div class="row">
        @foreach ([
            ['Total Lecturers', $stats['lecturers'], 'fa-chalkboard-teacher', '#e8f5e9', '#28a745'],
            ['Total Units', $stats['units'], 'fa-book', '#e3f2fd', '#1e88e5'],
            ['Total Departments', $stats['departments'], 'fa-building', '#fff3e0', '#fb8c00'],
            ['Hours Administered', number_format($stats['hours'], 1), 'fa-clock', '#f3e5f5', '#8e24aa'],
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
    <div class="row">
        @foreach ([
            ['Gross Amount', $totals->gross, ''],
            ['Tax', $totals->tax, 'deduction'],
            ['Housing Levy', $totals->housing, 'deduction'],
            ['Net Payable', $totals->net, 'text-success'],
        ] as [$label, $value, $class])
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card money-card h-100">
                    <div class="card-body">
                        <p class="label">{{ $label }}</p>
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
                            <span class="badge badge-pill {{ $row->hours > 0 ? 'badge-success' : 'badge-secondary' }}">{{ number_format($row->hours, 1) }} hrs</span>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">No lecturers yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card panel h-100">
                <div class="card-header"><i class="fas fa-file-invoice mr-1 text-success"></i> Payment Summary</div>
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
                            <span>{{ number_format($dept->hours, 1) }} hrs · {{ $money($dept->net) }}</span>
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
    var fmt = new Intl.NumberFormat('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    var money = function (v) { return 'KES ' + fmt.format(v); };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var detail = document.getElementById('lecturerDetail');

    function line(label, value, cls) {
        return '<div class="detail-row ' + (cls || '') + '"><span>' + label + '</span><span>' + value + '</span></div>';
    }

    function show(id) {
        var r = rows[id];
        if (!r) return;

        var units = Object.keys(r.units).map(function (u) {
            return '<li class="d-flex justify-content-between"><span>' + esc(u) + '</span><span>' + r.units[u].toFixed(1) + ' hrs</span></li>';
        }).join('');

        detail.innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-3">' +
                '<div><h4 class="mb-0 font-weight-bold">' + esc(r.name) + '</h4><span class="text-muted">' + esc(r.department) + '</span></div>' +
                '<a href="{{ url('lecturers') }}/' + r.id + '" class="btn btn-sm btn-outline-success">View lectures</a>' +
            '</div>' +
            (r.rate <= 0 && r.hours > 0 ? '<div class="alert alert-warning py-2 small">No rate per hour set for this lecturer.</div>' : '') +
            line('Lecture sessions', r.sessions) +
            line('Total hours', r.hours.toFixed(2) + ' hrs') +
            line('Rate per hour', money(r.rate)) +
            line('<strong>Gross amount</strong> <small class="text-muted">(hours × rate)</small>', '<strong>' + money(r.gross) + '</strong>') +
            line('Tax (' + r.tax_rate + '%)', '− ' + money(r.tax), 'deduction') +
            line('Housing levy (' + r.housing_levy_rate + '%)', '− ' + money(r.housing), 'deduction') +
            line('Net pay', money(r.net), 'net') +
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

    var first = document.querySelector('.lecturer-item');
    if (first) show(first.dataset.id);
})();
</script>
@endpush
