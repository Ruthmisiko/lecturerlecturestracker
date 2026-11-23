@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Staff Visits</h1>
            <small class="text-muted">Visits, exam supervision, sports escorts and other paid duties away from lecturing. A lecturer on a visit cannot also have lectures administered.</small>
        </div>
        <div>
            <a href="{{ route('staff-visits.report') }}" class="btn btn-outline-primary"><i class="fas fa-file-alt mr-1"></i> Claims Report</a>
            <a href="{{ route('travel-budgets.index') }}" class="btn btn-outline-primary"><i class="fas fa-wallet mr-1"></i> Travel Budgets</a>
            <a href="{{ route('staff-visits.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> Log Visit</a>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="row">
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Visits / days</small><div class="h4 mb-0">{{ $totals['count'] }} / {{ $totals['days'] }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Total payable</small><div class="h4 mb-0">{{ number_format($totals['amount'], 2) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Pending payment</small><div class="h4 mb-0 text-warning">{{ number_format($totals['pending'], 2) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Clashing with lectures</small><div class="h4 mb-0 {{ $totals['clashing'] ? 'text-danger' : 'text-success' }}">{{ $totals['clashing'] }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body pb-1">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small mb-0">Lecturer</label>
                    <select name="lecturer_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach ($lecturers as $l)
                            <option value="{{ $l->id }}" {{ request('lecturer_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">Type</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach (\App\Models\StaffVisit::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">Payment</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">Clash</label>
                    <select name="clash" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="yes" {{ request('clash') === 'yes' ? 'selected' : '' }}>Clashing with lectures</option>
                        <option value="no" {{ request('clash') === 'no' ? 'selected' : '' }}>No clash</option>
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <label class="small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="form-group col-md-1">
                    <label class="small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="form-group col-md-1 text-nowrap">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('staff-visits.index') }}" class="btn btn-sm btn-default">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Lecturer</th>
                        <th>Visit</th>
                        <th>Dates</th>
                        <th class="text-center">Days</th>
                        <th class="text-right">Per diem</th>
                        <th class="text-right">Fare / meals / other</th>
                        <th class="text-right">Total</th>
                        <th>Payment</th>
                        <th>Lecture clash</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        @php($clash = $clashes[$visit->id] ?? collect())
                        <tr class="{{ $clash->isNotEmpty() ? 'table-danger' : '' }}">
                            <td><strong>{{ $visit->lecturer->name ?? '—' }}</strong></td>
                            <td>
                                <span class="badge badge-info">{{ $visit->typeLabel() }}</span>
                                {{ $visit->purpose }}
                                @if ($visit->location)<br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $visit->location }}</small>@endif
                            </td>
                            <td class="text-nowrap">
                                {{ $visit->periodLabel() }}
                                <br><small class="text-muted">{{ $visit->isAllDay() ? 'All day' : substr($visit->start_time, 0, 5) . ' – ' . substr($visit->end_time, 0, 5) }}</small>
                            </td>
                            <td class="text-center">{{ $visit->days }}</td>
                            <td class="text-right">{{ number_format($visit->per_diem_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($visit->fare + $visit->meals + $visit->remuneration, 2) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($visit->total_amount, 2) }}</td>
                            <td class="text-nowrap">
                                @if ($visit->status === 'paid')
                                    <span class="badge badge-success">Paid</span>
                                    @if ($visit->paid_on)<br><small class="text-muted">{{ $visit->paid_on->format('d M Y') }}</small>@endif
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                    <form method="POST" action="{{ route('staff-visits.paid', $visit->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-link btn-sm p-0 ml-1">mark paid</button>
                                    </form>
                                @endif
                                @if ($visit->voucher_number)<br><small class="text-muted">Voucher {{ $visit->voucher_number }}</small>@endif
                            </td>
                            <td>
                                @if ($clash->isEmpty())
                                    <span class="text-success"><i class="fas fa-check-circle"></i> None</span>
                                @else
                                    <span class="badge badge-danger">{{ $clash->count() }} lecture(s) clash</span>
                                    <ul class="list-unstyled small mb-0 mt-1">
                                        @foreach ($clash->take(5) as $l)
                                            <li>
                                                <a href="{{ route('lecture-administereds.edit', $l->id) }}" class="text-danger">
                                                    {{ \Carbon\Carbon::parse($l->lecture_date)->format('d M') }}
                                                    {{ substr($l->start_time, 0, 5) }}–{{ substr($l->end_time, 0, 5) }}
                                                    {{ $l->unit->name ?? $l->classs->name ?? '' }}
                                                </a>
                                            </li>
                                        @endforeach
                                        @if ($clash->count() > 5)<li class="text-muted">+ {{ $clash->count() - 5 }} more</li>@endif
                                    </ul>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('staff-visits.edit', $visit->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('staff-visits.destroy', $visit->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this staff visit?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted p-5">
                                No staff visits found. <a href="{{ route('staff-visits.create') }}">Log the first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            <div class="card-footer">{{ $visits->links() }}</div>
        @endif
    </div>
</div>
@endsection
