@extends('layouts.app')

@section('content')
@php $n = fn ($v) => number_format($v, 4); @endphp

<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Lecturer Payment Report</h1>
            <small class="text-muted">
                Period: {{ !$from && !$to ? 'All time' : (($from ?: 'Start') . ' → ' . ($to ?: 'Today')) }} · Payable hrs: {{ \App\Http\Controllers\DashboardController::statusLabel($statuses) }} · Amounts in KES
            </small>
        </div>
        <div>
            <a href="{{ route('dashboard', request()->only('from', 'to', 'statuses')) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard
            </a>
            <a href="{{ route('dashboard.report', array_merge(request()->only('from', 'to', 'statuses'), ['format' => 'excel'])) }}" class="btn btn-success">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <a href="{{ route('dashboard.report', array_merge(request()->only('from', 'to', 'statuses'), ['format' => 'pdf'])) }}" class="btn btn-danger">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
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
            @foreach ($statuses ?? [] as $s)<input type="hidden" name="statuses[]" value="{{ $s }}">@endforeach
            <button class="btn btn-sm btn-success">Apply</button>
            <a href="{{ route('dashboard.report', request()->only('statuses')) }}" class="btn btn-sm btn-outline-secondary">All time</a>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Lecturer</th>
                        <th>Department</th>
                        <th>KRA PIN</th>
                        <th class="text-right">Total Hrs</th>
                        <th class="text-right">Rate / Hr</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Housing Levy</th>
                        <th class="text-right">Net Pay</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><a href="{{ route('lecturers.show', $r->id) }}">{{ $r->name }}</a></td>
                            <td>{{ $r->department }}</td>
                            <td>{{ $r->kra_pin }}</td>
                            <td class="text-right">{{ $n($r->hours) }}</td>
                            <td class="text-right">{!! $r->rate > 0 ? $n($r->rate) : '<span class="badge badge-warning">Not set</span>' !!}</td>
                            <td class="text-right">{{ $n($r->gross) }}</td>
                            <td class="text-right text-danger">{{ $n($r->tax) }}</td>
                            <td class="text-right text-danger">{{ $n($r->housing) }}</td>
                            <td class="text-right font-weight-bold text-success">{{ $n($r->net) }}</td>
                            <td class="text-right">
                                <a href="{{ route('dashboard.payslip', array_merge(['lecturer' => $r->id], request()->only('from', 'to', 'statuses'))) }}"
                                   class="btn btn-xs btn-outline-success" title="Print payslip"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted p-4">No lecturers found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold" style="background:#e9f6ec;">
                        <td colspan="4">TOTAL</td>
                        <td class="text-right">{{ $n($totals->hours) }}</td>
                        <td></td>
                        <td class="text-right">{{ $n($totals->gross) }}</td>
                        <td class="text-right text-danger">{{ $n($totals->tax) }}</td>
                        <td class="text-right text-danger">{{ $n($totals->housing) }}</td>
                        <td class="text-right text-success">{{ $n($totals->net) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
