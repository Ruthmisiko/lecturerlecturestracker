@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Payment Schedules</h1>
            <small class="text-muted">Bank payment lists for lecturers, ready to print or download.</small>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>
            <a href="{{ route('payment-schedules.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> New Schedule</a>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Period</th>
                        <th class="text-center">Rows</th>
                        <th class="text-right">Total (KES)</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td><a href="{{ route('payment-schedules.show', $schedule->id) }}" class="font-weight-bold">{{ $schedule->title }}</a></td>
                            <td>{{ $schedule->schedule_date->format('d.m.Y') }}</td>
                            <td class="text-muted small">
                                @if ($schedule->period_from || $schedule->period_to)
                                    {{ $schedule->period_from?->format('d M Y') ?? 'Start' }} – {{ $schedule->period_to?->format('d M Y') ?? 'Today' }}
                                @else
                                    All time
                                @endif
                            </td>
                            <td class="text-center">{{ $schedule->items->count() }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($schedule->total(), 2) }}</td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('payment-schedules.show', $schedule->id) }}" class="btn btn-sm btn-outline-success">View</a>
                                <a href="{{ route('payment-schedules.edit', $schedule->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <a href="{{ route('payment-schedules.pdf', $schedule->id) }}" class="btn btn-sm btn-danger" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                <a href="{{ route('payment-schedules.excel', $schedule->id) }}" class="btn btn-sm btn-success" title="Excel"><i class="fas fa-file-excel"></i></a>
                                <form method="POST" action="{{ route('payment-schedules.destroy', $schedule->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this payment schedule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="far fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted p-5">
                                No payment schedules yet.
                                <a href="{{ route('payment-schedules.create') }}">Create the first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($schedules->hasPages())
            <div class="card-footer">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection
