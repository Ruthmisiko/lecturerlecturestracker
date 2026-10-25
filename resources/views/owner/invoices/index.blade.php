@extends('owner.layout')
@section('title', 'Invoices')

@section('content')
@php $money = fn ($v, $cur = null) => ($cur ?? config('billing.currency')) . ' ' . number_format($v, 2); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
    <div>
        <h3 class="font-weight-bold mb-0">Invoices</h3>
        <small class="text-muted">Bills issued to institutions</small>
    </div>
    <a href="{{ route('owner.invoices.create') }}" class="btn btn-orange"><i class="fas fa-plus mr-1"></i> New Invoice</a>
</div>

<div class="row">
    @foreach ([
        ['Outstanding', $money($summary['outstanding']), 'Draft and sent, not yet paid', '#2d2a6b'],
        ['Overdue', $money($summary['overdue']), 'Unpaid and past the due date', '#b42318'],
        ['Paid', $money($summary['paid']), 'Received to date', '#18794e'],
        ['Invoices', number_format($summary['count']), 'Issued in total', '#1f2333'],
    ] as [$label, $value, $sub, $color])
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card stat h-100">
                <div class="card-body">
                    <p class="label">{{ $label }}</p>
                    <p class="value" style="color:{{ $color }}; font-size:24px;">{{ $value }}</p>
                    <small class="text-muted">{{ $sub }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<form method="GET" class="card mb-3">
    <div class="card-body py-3 d-flex flex-wrap align-items-end" style="gap:8px;">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Invoice no. or name…" style="width:200px;">
        <select name="institution_id" class="form-control form-control-sm" style="width:200px;">
            <option value="">All institutions</option>
            @foreach ($institutionOptions as $opt)
                <option value="{{ $opt->id }}" {{ request('institution_id') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control form-control-sm" style="width:150px;">
            <option value="">All statuses</option>
            @foreach (\App\Models\Invoice::STATUSES + ['overdue' => 'Overdue'] as $key => $label)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-navy">Filter</button>
        <a href="{{ route('owner.invoices.index') }}" class="btn btn-sm btn-light border">Clear</a>
    </div>
</form>

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Invoice</th><th>Bill to</th><th>Issued</th><th>Due</th><th class="text-right">Amount</th><th>Status</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    @php
                        $status = $inv->displayStatus();
                        $badge = ['Paid' => 'badge-active', 'Overdue' => 'badge-inactive', 'Cancelled' => 'badge-secondary', 'Sent' => 'badge-info', 'Draft' => 'badge-light border'][$status] ?? 'badge-light';
                    @endphp
                    <tr>
                        <td><a href="{{ route('owner.invoices.show', $inv->id) }}" class="font-weight-bold">{{ $inv->number }}</a></td>
                        <td>{{ $inv->bill_to_name }}<div class="small text-muted">{{ $inv->bill_to_email }}</div></td>
                        <td class="small">{{ $inv->issue_date?->format('d M Y') }}</td>
                        <td class="small">{{ $inv->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-right font-weight-bold">{{ $money($inv->total, $inv->currency) }}</td>
                        <td><span class="badge {{ $badge }}">{{ $status }}</span></td>
                        <td class="text-right text-nowrap">
                            @if ($inv->status !== 'paid')
                                <form method="POST" action="{{ route('owner.invoices.status', $inv->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="paid">
                                    <button class="btn btn-sm btn-outline-success" title="Mark as paid"><i class="fas fa-check"></i></button>
                                </form>
                            @endif
                            <a href="{{ route('owner.invoices.pdf', $inv->id) }}" class="btn btn-sm btn-light border" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                            <a href="{{ route('owner.invoices.edit', $inv->id) }}" class="btn btn-sm btn-light border" title="Edit"><i class="far fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted p-5">No invoices yet. <a href="{{ route('owner.invoices.create') }}">Create the first one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links('pagination::bootstrap-4') }}</div>@endif
</div>
@endsection
