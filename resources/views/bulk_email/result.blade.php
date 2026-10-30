@extends('layouts.app')

@php
    $counts = $results->countBy('status');
    $badges = ['sent' => 'badge-success', 'skipped' => 'badge-warning', 'failed' => 'badge-danger'];
@endphp

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Bulk Email Sent</h1>
            <small class="text-muted">Subject: {{ $subject }}</small>
        </div>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to Bulk Email</a>
    </div>
</section>

<div class="content px-3">
    <div class="alert {{ ($counts['failed'] ?? 0) ? 'alert-warning' : 'alert-success' }}">
        <strong>{{ $counts['sent'] ?? 0 }}</strong> sent,
        <strong>{{ $counts['skipped'] ?? 0 }}</strong> skipped,
        <strong>{{ $counts['failed'] ?? 0 }}</strong> failed.
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr><th>Lecturer</th><th>Email</th><th>Result</th><th>Note</th></tr>
                </thead>
                <tbody>
                    @foreach ($results->sortBy(fn ($r) => ['failed' => 0, 'skipped' => 1, 'sent' => 2][$r['status']]) as $r)
                        <tr>
                            <td>{{ $r['name'] }}</td>
                            <td class="text-muted">{{ $r['to'] ?: '—' }}</td>
                            <td><span class="badge {{ $badges[$r['status']] }}">{{ ucfirst($r['status']) }}</span></td>
                            <td class="small">{{ $r['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
