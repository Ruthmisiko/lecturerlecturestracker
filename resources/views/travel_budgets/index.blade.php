@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Travel Budgets</h1>
            <small class="text-muted">How much of each department's travel allocation has been spent on staff visits. Set a department's budget under Departments.</small>
        </div>
        <a href="{{ route('staff-visits.index') }}" class="btn btn-default">Back to Staff Visits</a>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body pb-1">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small mb-0">Financial year</label>
                    <select class="form-control form-control-sm" id="fy">
                        <option value="">Custom dates…</option>
                        @foreach ($years as $y)
                            <option value="{{ $y['from'] }}|{{ $y['to'] }}" {{ $from->toDateString() === $y['from'] && $to->toDateString() === $y['to'] ? 'selected' : '' }}>{{ $y['label'] }} (Jul – Jun)</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">From</label>
                    <input type="date" name="from" id="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">To</label>
                    <input type="date" name="to" id="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-sm btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Total allocated</small><div class="h4 mb-0">{{ number_format($totals->allocated, 2) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Total spent</small><div class="h4 mb-0">{{ number_format($totals->spent, 2) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Balance (budgeted depts)</small><div class="h4 mb-0 {{ $totals->balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totals->balance, 2) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Exhausted</small><div class="h4 mb-0 {{ $totals->exhausted ? 'text-danger' : 'text-success' }}">{{ $totals->exhausted }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Department</th>
                        <th class="text-right">Budget</th>
                        <th class="text-right">Spent</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Not yet paid</th>
                        <th class="text-right">Balance</th>
                        <th style="min-width:200px;">Used</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php($bar = ['exhausted' => 'bg-danger', 'near' => 'bg-warning', 'ok' => 'bg-success', 'none' => 'bg-secondary'][$r->state])
                        <tr>
                            <td>
                                <strong>{{ $r->name }}</strong>
                                @if ($r->members)<br><small class="text-muted">Includes {{ implode(', ', $r->members) }}</small>@endif
                                <br><small class="text-muted">{{ $r->visits }} visit(s)</small>
                            </td>
                            <td class="text-right">{{ $r->allocated !== null ? number_format($r->allocated, 2) : '—' }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($r->spent, 2) }}</td>
                            <td class="text-right">{{ number_format($r->paid, 2) }}</td>
                            <td class="text-right">{{ number_format($r->pending, 2) }}</td>
                            <td class="text-right {{ $r->balance !== null && $r->balance < 0 ? 'text-danger font-weight-bold' : '' }}">{{ $r->balance !== null ? number_format($r->balance, 2) : '—' }}</td>
                            <td>
                                @if ($r->percent !== null)
                                    <div class="progress" style="height:14px;">
                                        <div class="progress-bar {{ $bar }}" style="width: {{ min(100, $r->percent) }}%">{{ $r->percent }}%</div>
                                    </div>
                                @else
                                    <small class="text-muted">No budget set</small>
                                @endif
                            </td>
                            <td>
                                @if ($r->state === 'exhausted')<span class="badge badge-danger">Exhausted</span>
                                @elseif ($r->state === 'near')<span class="badge badge-warning">Nearly exhausted</span>
                                @elseif ($r->state === 'ok')<span class="badge badge-success">Within budget</span>
                                @else<span class="badge badge-secondary">—</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted p-5">No departments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('fy').addEventListener('change', function () {
        if (!this.value) return;
        const [f, t] = this.value.split('|');
        document.getElementById('from').value = f;
        document.getElementById('to').value = t;
        this.form.submit();
    });
</script>
@endpush
