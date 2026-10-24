@extends('layouts.app')

@push('styles')
<style>
    @include('payment_schedules._sheet_styles')
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <h1 class="font-weight-bold mb-0">Payment Schedule</h1>
        <div>
            <a href="{{ route('payment-schedules.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> All Schedules</a>
            <a href="{{ route('payment-schedules.edit', $schedule->id) }}" class="btn btn-warning"><i class="far fa-edit mr-1"></i> Edit</a>
            <a href="{{ route('payment-schedules.pdf', $schedule->id) }}" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
            <a href="{{ route('payment-schedules.excel', $schedule->id) }}" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-body table-responsive">
            @include('payment_schedules._sheet')
        </div>
    </div>
</div>
@endsection
