@extends('layouts.app')

@push('styles')
<style>
    table.claims { width: 100%; border-collapse: collapse; font-size: 12px; }
    table.claims th { background: #1F4E79; color: #fff; padding: 6px 5px; border: 1px solid #333; text-align: center; }
    table.claims td { padding: 4px 5px; border: 1px solid #333; vertical-align: top; }
    table.claims tbody tr:hover td { background: #eef4fb; }
    table.claims .c { text-align: center; } table.claims .r { text-align: right; } table.claims .nw { white-space: nowrap; }
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Staff Claims Report</h1>
            <small class="text-muted">Voucher-ready list of staff visit claims, as printed for payment.</small>
        </div>
        <div>
            <a href="{{ route('staff-visits.index') }}" class="btn btn-default">Back to Staff Visits</a>
            <a href="{{ route('staff-visits.report.excel', request()->query()) }}" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Excel</a>
            <a href="{{ route('staff-visits.report.pdf', request()->query()) }}" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body pb-1">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-2">
                    <label class="small mb-0">Claims</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="pending" {{ request('status', 'pending') === 'pending' ? 'selected' : '' }}>Pending (not paid)</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">As at (entry date)</label>
                    <input type="date" name="as_at" class="form-control form-control-sm" value="{{ request('as_at', now()->toDateString()) }}">
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">Department</label>
                    <select name="department_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small mb-0">Lecturer</label>
                    <select name="lecturer_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach ($lecturers as $l)
                            <option value="{{ $l->id }}" {{ request('lecturer_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <label class="small mb-0">Visits from</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="form-group col-md-1">
                    <label class="small mb-0">to</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="form-group col-md-2 text-nowrap">
                    <button class="btn btn-sm btn-primary">Apply</button>
                    <a href="{{ route('staff-visits.report') }}" class="btn btn-sm btn-default">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            @include('staff_visits._claims_table')
        </div>
    </div>
</div>
@endsection
