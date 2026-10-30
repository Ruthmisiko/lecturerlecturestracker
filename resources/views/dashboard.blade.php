@extends('layouts.app')

@push('styles')
<style>
    .metric { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); position: relative; border-left: 4px solid {{ \App\Support\Theme::current()->primary }}; }
    .metric .card-body { padding: 18px 20px; }
    .metric .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 4px; }
    .metric .value { font-size: 26px; font-weight: 700; margin: 0; line-height: 1.2; color: #1f2937; }
    .metric.metric-money .value { font-size: 20px; }
    .metric .metric-icon { position: absolute; top: 16px; right: 16px; width: 22px; height: 22px; color: #b6bcc8; }
    .panel { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .panel .card-header { background: #fff; border-bottom: 1px solid #eef0f2; font-weight: 600; }
    .lecturer-list { max-height: 460px; overflow-y: auto; }
    .lecturer-item { cursor: pointer; border-left: 3px solid transparent; color: #212529; }
    .lecturer-item:hover { background: #f8f9fa; }
    .lecturer-item.selected { background: var(--primary-soft); border-left: 4px solid var(--primary); }
    .lecturer-item.selected .name { color: #212529; }
    .lecturer-item .dept { font-size: 12px; color: #6c757d; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; }
    .detail-row:last-child { border-bottom: 0; }
    .detail-row.net { font-size: 18px; font-weight: 700; color: var(--primary-dark); border-top: 2px solid var(--primary); border-bottom: 0; margin-top: 6px; }
    .deduction { color: #c0392b; }
    .bar { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; }
    .bar > span { display: block; height: 100%; background: var(--primary); }
    .empty-detail { color: #6c757d; text-align: center; padding: 60px 20px; }
    .chart-box { position: relative; height: 260px; }
    html { scroll-behavior: smooth; }
    .narration-box { background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; }
    .narration-text { white-space: pre-line; font-size: 14px; line-height: 1.45; color: #333; }

    /* Dashboard uses the institution's secondary colour to balance the primary-coloured sidebar. */
    {!! \App\Support\Theme::current()->secondaryScope('.dash-theme') !!}
    .dash-theme .detail-row.net { color: #212529; }
    @php $t = \App\Support\Theme::current(); @endphp
    .btn-outline-brand { color: {{ $t->primary }}; border-color: {{ $t->primary }}; background: transparent; }
    .btn-outline-brand:hover, .btn-outline-brand:focus { background: {{ $t->primary }}; color: {{ $t->primaryText() }}; }
    .btn-brand { background: {{ $t->primary }}; border-color: {{ $t->primary }}; color: {{ $t->primaryText() }}; }
    .btn-brand:hover, .btn-brand:focus { background: {{ $t->primaryDark() }}; border-color: {{ $t->primaryDark() }}; color: {{ $t->primaryText() }}; }
</style>
@endpush

@section('content')
<div class="dash-theme">
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
        <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="#reports" class="btn btn-success" id="toReports">
                <i class="fas fa-chart-bar mr-1"></i> Reports
            </a>
            @if (Auth::user()->hasAccess('payment-schedule.manage'))
            <a href="{{ route('payment-schedules.index') }}" class="btn btn-brand">
                <i class="fas fa-university mr-1"></i> Payment Schedule
            </a>
            @endif
            @if (Auth::user()->hasAccess('payment-report.view'))
            <a href="{{ route('dashboard.report', request()->only('from', 'to', 'statuses')) }}" class="btn btn-success">
                <i class="fas fa-file-invoice-dollar mr-1"></i> Payment Report
            </a>
            @endif
            @if (Auth::user()->hasAccess('bulk-email.send'))
            <a href="{{ route('bulk-email.create', request()->only('from', 'to', 'statuses')) }}" class="btn btn-brand">
                <i class="fas fa-mail-bulk mr-1"></i> Bulk Email
            </a>
            @endif
        </div>
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
    @php
        // Heroicons (outline, MIT) – 24x24 stroke paths.
        $icons = [
            'lecturers'   => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5',
            'units'       => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
            'departments' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z',
            'hours'       => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            'gross'       => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
            'tax'         => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
            'levy'        => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
            'employer'    => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-2.625c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        ];
        $svg = fn ($key) => '<svg class="metric-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $icons[$key] . '"/></svg>';
    @endphp
    <div class="row">
        @foreach ([
            ['Total Lecturers', $stats['lecturers'], 'lecturers'],
            ['Total Units', $stats['units'], 'units'],
            ['Total Departments', $stats['departments'], 'departments'],
            ['Hours Administered', number_format($stats['hours'], 2), 'hours'],
        ] as [$label, $value, $icon])
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card metric h-100">
                    <div class="card-body">
                        {!! $svg($icon) !!}
                        <p class="label">{{ $label }}</p>
                        <p class="value">{{ $value }}</p>
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
            ['Total Gross Amount', $totals->gross, '', 'gross'],
            ['Total Tax (PAYE)' . $rateLabel('tax_rate'), $totals->tax, 'deduction', 'tax'],
            ['Housing Levy' . $rateLabel('housing_levy_rate'), $totals->housing, 'deduction', 'levy'],
            ['Employer Levy' . $rateLabel('housing_levy_rate'), $totals->employer_levy, '', 'employer'],
        ] as [$label, $value, $class, $icon])
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card metric metric-money h-100">
                    <div class="card-body">
                        {!! $svg($icon) !!}
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

    {{-- Email payslip --}}
    @php
        $periodText = !$from && !$to ? 'all lectures to date' : (($from ? \Carbon\Carbon::parse($from)->format('d M Y') : 'the start') . ' to ' . ($to ? \Carbon\Carbon::parse($to)->format('d M Y') : 'today'));
        $institutionName = \App\Models\Institution::find(Auth::user()->currentInstitutionId())?->name;
    @endphp
    <div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-labelledby="emailModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form class="modal-content" id="emailForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalTitle"><i class="far fa-envelope mr-1"></i> Email Payslip</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    @if ($emailConfigs->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No email account is set up yet. Add one under
                            <a href="{{ route('email-configurations.index') }}">Email Settings</a>, then come back.
                        </div>
                    @else
                        <div class="form-group">
                            <label>Send from</label>
                            @if ($emailConfigs->count() > 1)
                                <select name="email_configuration_id" class="form-control">
                                    @foreach ($emailConfigs as $cfg)
                                        <option value="{{ $cfg->id }}" {{ $cfg->is_default ? 'selected' : '' }}>{{ $cfg->name }} &lt;{{ $cfg->from_address }}&gt;{{ $cfg->is_default ? ' (default)' : '' }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="email_configuration_id" value="{{ $emailConfigs->first()->id }}">
                                <div class="form-control-plaintext font-weight-bold">{{ $emailConfigs->first()->name }} &lt;{{ $emailConfigs->first()->from_address }}&gt;</div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>To (lecturer's email)</label>
                            <input type="email" name="to" id="emailTo" class="form-control" required maxlength="190">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="save_email" value="1" id="saveEmail">
                                <label class="form-check-label small" for="saveEmail">Save this as the lecturer's email address</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="emailTemplate">Template</label>
                            <select id="emailTemplate" class="form-control">
                                <option value="">Standard message</option>
                                @foreach ($emailTemplates as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }}{{ $tpl->is_default ? ' (default)' : '' }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Picking a template fills in the subject and message below.
                                @if (Auth::user()->hasAccess('email-template.manage'))
                                    <a href="{{ route('email-templates.index') }}">Manage templates</a>
                                @endif
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" id="emailSubject" class="form-control" required maxlength="200" data-placeholder-target>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="body" id="emailBody" class="form-control" rows="9" required maxlength="10000" data-placeholder-target></textarea>
                        </div>
                        <details class="mb-3">
                            <summary class="small font-weight-bold" style="cursor:pointer;"><i class="fas fa-plus-circle mr-1"></i> Insert lecturer details</summary>
                            <p class="small text-muted mt-2 mb-2">Click in the subject or message, then click a detail to insert this lecturer's value there.</p>
                            @include('email_templates._placeholders')
                        </details>
                        <div class="small text-muted"><i class="fas fa-paperclip mr-1"></i> Attachment: <span id="emailAttachment"></span> (payslip for the period and payable hours shown on the dashboard)</div>
                        <div class="alert mt-3 mb-0 d-none" id="emailResult"></div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    @if ($emailConfigs->isNotEmpty())
                        <button type="submit" class="btn btn-success" id="emailSend"><i class="far fa-paper-plane mr-1"></i> Send</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Reports (charts) --}}
    <div id="reports" class="pt-2">
        <h4 class="font-weight-bold mb-3"><i class="fas fa-chart-bar mr-1 text-success"></i> Reports</h4>

        @if ($charts->isEmpty())
            <div class="card panel"><div class="card-body text-muted">No departments yet.</div></div>
        @else
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card panel h-100">
                        <div class="card-header">Lecturers per Department</div>
                        <div class="card-body"><div class="chart-box"><canvas id="chartLecturers" role="img" aria-label="Lecturers per department"></canvas></div></div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card panel h-100">
                        <div class="card-header">Units per Department</div>
                        <div class="card-body"><div class="chart-box"><canvas id="chartUnits" role="img" aria-label="Units per department"></canvas></div></div>
                    </div>
                </div>
            </div>

            <div class="card panel mb-3">
                <div class="card-header">Hours &amp; Net Payment per Department</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <h6 class="text-muted small text-uppercase mb-2">Total hours</h6>
                            <div class="chart-box"><canvas id="chartHours" role="img" aria-label="Total hours per department"></canvas></div>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="text-muted small text-uppercase mb-2">Share of net payment (KES)</h6>
                            <div class="chart-box"><canvas id="chartPay" role="img" aria-label="Total net payment per department"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card panel mb-4">
                <div class="card-header">Approved vs Contracted Hours per Department</div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="chartApproved" role="img" aria-label="Approved versus contracted hours per department"></canvas></div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm mb-0 small">
                            <thead class="thead-light">
                                <tr><th>Department</th><th class="text-right">Approved hrs</th><th class="text-right">Contracted hrs</th><th class="text-right">Difference</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($charts as $c)
                                    <tr>
                                        <td>{{ $c['name'] }}</td>
                                        <td class="text-right">{{ $c['approved'] !== null ? number_format($c['approved'], 2) : 'Not set' }}</td>
                                        <td class="text-right">{{ number_format($c['hours'], 2) }}</td>
                                        <td class="text-right">
                                            @if ($c['approved'] === null)
                                                <span class="text-muted">—</span>
                                            @elseif ($c['hours'] > $c['approved'])
                                                <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i>Over by {{ number_format($c['hours'] - $c['approved'], 2) }}</span>
                                            @else
                                                <span>{{ number_format($c['approved'] - $c['hours'], 2) }} remaining</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Contracted hours are the lecture hours recorded in the system for the period and payable statuses chosen above. Approved hours are set on each department.</small>
                </div>
            </div>
        @endif
    </div>
</div>
</div>{{-- .dash-theme --}}
@endsection

@push('scripts')
<script>
(function () {
    var rows = @json($rows->keyBy('id'));
    @php $payslipUrl = route('dashboard.payslip', array_merge(['lecturer' => '__ID__'], request()->only('from', 'to', 'statuses'))); @endphp
    var payslipUrl = @json($payslipUrl);
    var canEmail = @json(Auth::user()->hasAccess('payslip.email'));
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
            'Gross Amount: ' + ksh(r.gross) + ' (' + hours + ' × ' + rate + ')\n' +
            'PAYE (' + num(r.tax_rate, 2) + '%): ' + ksh(r.tax) + '\n' +
            'Affordable Housing Levy (AHL) (' + num(r.housing_levy_rate, 2) + '%): ' + ksh(r.housing) + '\n' +
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

        var units = (r.unit_details || []).map(function (u) {
            var tag = u.max !== null
                ? ' <span class="text-muted">(max ' + num(u.max, 2) + ' hrs)</span>' : '';
            var over = u.capped
                ? '<div class="text-danger font-weight-bold" style="font-size:12px;"><i class="fas fa-exclamation-triangle mr-1"></i>Exceeded max: ' +
                  num(u.actual, 2) + ' hrs recorded, only ' + num(u.counted, 2) + ' paid</div>' : '';
            return '<li class="mb-1"><div class="d-flex justify-content-between"><span>' + esc(u.name) + tag + '</span><span>' +
                u.counted.toFixed(4) + ' hrs</span></div>' + over + '</li>';
        }).join('');

        detail.innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-3">' +
                '<div><h4 class="mb-0 font-weight-bold">' + esc(r.name) + '</h4><span class="text-muted">' + esc(r.department) + '</span></div>' +
                '<div class="text-nowrap">' +
                    '<a href="{{ url('lecturers') }}/' + r.id + '" class="btn btn-sm btn-outline-brand mr-1">View lectures</a>' +
                    '<a href="' + payslipUrl.replace('__ID__', r.id) + '" class="btn btn-sm btn-success mr-1"><i class="fas fa-print mr-1"></i>Print Payslip</a>' +
                    (canEmail ? '<button type="button" class="btn btn-sm btn-brand email-payslip" data-id="' + r.id + '"><i class="far fa-envelope mr-1"></i>Email Payslip</button>' : '') +
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
            (units ? '<h6 class="mt-4 font-weight-bold">Hours by unit</h6><ul class="list-unstyled small mb-0">' + units + '</ul>' : '') +
            (r.capped_hours > 0 ? '<p class="small text-danger mt-2 mb-0">' + num(r.capped_hours, 2) + ' hrs above unit maximums are not paid.</p>' : '') +
            (r.gross > 0
                ? '<div class="narration-box mt-3">' +
                      '<div class="d-flex justify-content-between align-items-center mb-2">' +
                          '<strong><i class="fas fa-align-left mr-1 text-success"></i> Narration</strong>' +
                          '<button type="button" class="btn btn-sm btn-outline-success" id="copyNarration"><i class="far fa-copy mr-1"></i>Copy</button>' +
                      '</div>' +
                      '<div class="narration-text" id="narrationText">' + esc(narration(r)) + '</div>' +
                  '</div>'
                : '') +
            (lastEmailed[r.id] ? '<p class="small text-success mt-3 mb-0"><i class="far fa-envelope mr-1"></i>Payslip last emailed to ' + esc(lastEmailed[r.id].to) + ' on ' + esc(lastEmailed[r.id].at) + '</p>' : '') +
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

    // ---- Email payslip ----
    @php
        $emailUrl = route('dashboard.payslip.email', ['lecturer' => '__ID__']) . '?' . http_build_query(request()->only('from', 'to', 'statuses'));
        $lastEmailedJs = (object) $lastEmailed->all();
    @endphp
    var emailUrl     = @json($emailUrl);
    var lastEmailed  = @json($lastEmailedJs);
    var periodText   = @json($periodText);
    var senderName   = @json($institutionName ?? '');
    var templates    = @json($emailTemplates->keyBy('id'));
    var defaultTpl   = @json($emailTemplates->firstWhere('is_default', true)?->id);
    var emailFor     = null;

    // Fills the subject and message from the chosen template ('' = the standard message).
    function applyTemplate(tplId) {
        var r = rows[emailFor], tpl = templates[tplId];
        var subject = tpl ? tpl.subject : 'Payslip – {name} – {period}';
        var body = tpl ? tpl.body :
            'Dear {name},\n\nPlease find attached your payslip for {period}.\n\n' +
            'Hours: {hours}\nGross amount: {gross}\nNet pay: {net}\n\nKind regards,\n{institution}';
        document.getElementById('emailSubject').value = fillPlaceholders(subject);
        document.getElementById('emailBody').value = fillPlaceholders(body);
    }

    // The selected lecturer's value for each {placeholder} (see EmailTemplate::PLACEHOLDERS).
    function placeholderValues(r) {
        var missing = '(not on record)';
        var date = function (d) {
            return d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
        };
        var units = (r.unit_details || []).map(function (u) {
            return '- ' + (u.code ? u.code + ' ' : '') + u.name + ': ' + num(u.counted, 2) + ' hrs';
        }).join('\n');

        return {
            name: r.name, email: r.email || missing, phone: r.phone || missing, id_number: r.id_number || missing,
            kra_pin: r.kra_pin && r.kra_pin !== '—' ? r.kra_pin : missing, department: r.department || '',
            account_number: r.account_number || missing, bank: r.bank_name || missing, branch: r.bank_branch || missing,
            period: periodText, period_taught: r.period_taught || '—', sessions: r.sessions, hours: num(r.hours, 2),
            units: units || 'No units', last_lecture: date(r.last_lecture),
            rate: ksh(r.rate), gross: ksh(r.gross), tax_rate: num(r.tax_rate, 2) + '%', tax: ksh(r.tax),
            housing_levy_rate: num(r.housing_levy_rate, 2) + '%', housing_levy: ksh(r.housing), net: ksh(r.net),
            institution: senderName || 'Accounts Office', date: date(new Date())
        };
    }

    function fillPlaceholders(text) {
        var values = placeholderValues(rows[emailFor]);
        return text.replace(/\{(\w+)\}/g, function (m, key) { return values.hasOwnProperty(key) ? values[key] : m; });
    }

    // Clicking a detail in the Email Payslip window inserts the lecturer's value, not the tag.
    window.resolvePlaceholder = function (tag) { return emailFor ? fillPlaceholders(tag) : tag; };

    function openEmail(id) {
        var r = rows[id];
        if (!r || !document.getElementById('emailTo')) { $('#emailModal').modal('show'); return; }
        emailFor = id;
        document.getElementById('emailTo').value = r.email || '';
        document.getElementById('saveEmail').checked = false;
        document.getElementById('emailTemplate').value = defaultTpl || '';
        applyTemplate(defaultTpl || '');
        document.getElementById('emailAttachment').textContent =
            'payslip_' + r.name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') + '.pdf';
        document.getElementById('emailResult').className = 'alert mt-3 mb-0 d-none';
        $('#emailModal').modal('show');
    }

    detail.addEventListener('click', function (e) {
        var btn = e.target.closest('.email-payslip');
        if (btn) openEmail(btn.dataset.id);
    });

    var templateSelect = document.getElementById('emailTemplate');
    if (templateSelect) {
        templateSelect.addEventListener('change', function () { if (emailFor) applyTemplate(this.value); });
    }

    var emailForm = document.getElementById('emailForm');
    emailForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!emailFor) return;
        // Fill any {placeholders} typed by hand.
        ['emailSubject', 'emailBody'].forEach(function (id) {
            var field = document.getElementById(id);
            field.value = fillPlaceholders(field.value);
        });
        var send = document.getElementById('emailSend'), res = document.getElementById('emailResult');
        send.disabled = true;
        send.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending…';
        fetch(emailUrl.replace('__ID__', emailFor), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            body: new FormData(emailForm)
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                var msg = x.j.errors ? Object.values(x.j.errors)[0][0] : (x.j.message || 'Something went wrong.');
                res.className = 'alert mt-3 mb-0 ' + (x.ok ? 'alert-success' : 'alert-danger');
                res.textContent = msg;
                if (x.ok) {
                    var to = document.getElementById('emailTo').value;
                    lastEmailed[emailFor] = { to: to, at: x.j.sent_at };
                    if (document.getElementById('saveEmail').checked) rows[emailFor].email = to;
                    show(emailFor);
                }
            })
            .catch(function () { res.className = 'alert mt-3 mb-0 alert-danger'; res.textContent = 'Could not reach the server. Please try again.'; })
            .finally(function () { send.disabled = false; send.innerHTML = '<i class="far fa-paper-plane mr-1"></i> Send'; });
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

@push('scripts')
@php $chartTheme = \App\Support\Theme::current(); @endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    var data = @json($charts);
    if (!data.length || !window.Chart) return;

    var BRAND    = @json($chartTheme->primary);   // single-series bars use the institution colour
    var APPROVED = '#2a78d6';                      // validated categorical pair (blue / orange)
    var CONTRACT = '#eb6834';
    var INK = '#52514e', GRID = 'rgba(0,0,0,.06)';
    var names = data.map(function (d) { return d.name; });
    var num = function (v, dp) { return Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: dp || 0, maximumFractionDigits: dp || 0 }); };

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = INK;

    // Departments keep one colour across both round charts (fixed order, never re-assigned by size).
    var SLOTS = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
    var deptColor = names.map(function (n, i) { return i < 8 ? SLOTS[i] : '#a8a7a0'; });

    // Total in the middle of a doughnut.
    var centerText = {
        id: 'centerText',
        afterDraw: function (chart, args, opts) {
            if (!opts || !opts.text) return;
            var a = chart.chartArea, ctx = chart.ctx, x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
            ctx.save();
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillStyle = '#0b0b0b'; ctx.font = '700 22px ' + Chart.defaults.font.family;
            ctx.fillText(opts.text, x, y - 8);
            ctx.fillStyle = INK; ctx.font = '12px ' + Chart.defaults.font.family;
            ctx.fillText(opts.sub || '', x, y + 14);
            ctx.restore();
        }
    };

    function round(id, type, values, unit, format, center) {
        var total = values.reduce(function (t, v) { return t + v; }, 0);
        return new Chart(document.getElementById(id), {
            type: type,
            plugins: [centerText],
            data: { labels: names, datasets: [{ data: values, backgroundColor: deptColor, borderColor: '#fff', borderWidth: 2, hoverOffset: 6 }] },
            options: {
                maintainAspectRatio: false,
                cutout: type === 'doughnut' ? '62%' : 0,
                plugins: {
                    centerText: center ? { text: format(total), sub: center } : {},
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 3, padding: 12,
                            // Legend shows value and share so identity never relies on colour alone.
                            generateLabels: function (chart) {
                                return names.map(function (n, i) {
                                    var pct = total ? Math.round(values[i] / total * 100) : 0;
                                    return { text: n + ': ' + format(values[i]) + ' (' + pct + '%)', fillStyle: deptColor[i],
                                             strokeStyle: deptColor[i], fontColor: INK, index: i, hidden: !chart.getDataVisibility(i) };
                                });
                            }
                        },
                        onClick: function (e, item, legend) { legend.chart.toggleDataVisibility(item.index); legend.chart.update(); }
                    },
                    tooltip: { callbacks: { label: function (c) {
                        var pct = total ? (c.raw / total * 100).toFixed(1) : 0;
                        return ' ' + c.label + ': ' + format(c.raw) + (unit ? ' ' + unit : '') + ' (' + pct + '%)';
                    } } }
                }
            }
        });
    }

    function columns(id, values, label, format) {
        return new Chart(document.getElementById(id), {
            type: 'bar',
            data: { labels: names, datasets: [{ label: label, data: values, backgroundColor: BRAND,
                borderRadius: 4, borderSkipped: 'start', maxBarThickness: 46 }] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + label + ': ' + format(c.raw); } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: GRID }, border: { display: false },
                         ticks: { precision: 0, callback: function (v) { return num(v); } } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    round('chartLecturers', 'doughnut', data.map(function (d) { return d.lecturers; }), 'lecturers', function (v) { return num(v); }, 'lecturers');
    columns('chartUnits', data.map(function (d) { return d.units; }), 'Units', function (v) { return num(v); });
    columns('chartHours', data.map(function (d) { return d.hours; }), 'Hours', function (v) { return num(v, 2) + ' hrs'; });
    round('chartPay', 'pie', data.map(function (d) { return d.net; }), '', function (v) { return 'KES ' + num(v); }, null);

    // Approved vs contracted: paired columns on one hours axis, legend + table below.
    new Chart(document.getElementById('chartApproved'), {
        type: 'bar',
        data: {
            labels: names,
            datasets: [
                { label: 'Approved hours', data: data.map(function (d) { return d.approved; }), backgroundColor: APPROVED,
                  borderRadius: 4, borderSkipped: 'start', maxBarThickness: 40 },
                { label: 'Contracted hours', data: data.map(function (d) { return d.hours; }), backgroundColor: CONTRACT,
                  borderRadius: 4, borderSkipped: 'start', maxBarThickness: 40 }
            ]
        },
        options: {
            maintainAspectRatio: false,
            datasets: { bar: { categoryPercentage: 0.6, barPercentage: 0.9 } },
            plugins: {
                legend: { position: 'top', align: 'start', labels: { boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 3 } },
                tooltip: {
                    mode: 'index', intersect: false,
                    callbacks: {
                        label: function (c) { return ' ' + c.dataset.label + ': ' + (c.raw === null ? 'Not set' : num(c.raw, 2) + ' hrs'); },
                        footer: function (items) {
                            var d = data[items[0].dataIndex];
                            if (d.approved === null) return 'Approved hours not set';
                            var diff = d.hours - d.approved;
                            return diff > 0 ? 'Over approved by ' + num(diff, 2) + ' hrs' : num(-diff, 2) + ' hrs remaining';
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: GRID }, border: { display: false }, title: { display: true, text: 'Hours' } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    document.getElementById('toReports').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('reports').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();
</script>
@endpush
