@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <h1 class="font-weight-bold mb-0">Bulk Upload: {{ $report['what'] }}</h1>
        <a href="{{ $backUrl }}" class="btn btn-success"><i class="fas fa-arrow-left mr-1"></i> {{ $backLabel }}</a>
    </div>
</section>

<div class="content px-3">
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card metric h-100"><div class="card-body">
                <p class="label">Added</p>
                <p class="value text-success">{{ $report['created'] }}</p>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card metric h-100"><div class="card-body">
                <p class="label">Skipped (problems)</p>
                <p class="value {{ count($report['skipped']) ? 'text-danger' : '' }}">{{ count($report['skipped']) }}</p>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card metric h-100"><div class="card-body">
                <p class="label">Warnings</p>
                <p class="value">{{ count($report['warnings']) }}</p>
            </div></div>
        </div>
    </div>

    @if ($report['created'] && !count($report['skipped']))
        <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i> All rows were added successfully.</div>
    @endif

    @if (count($report['skipped']))
        <div class="card mb-3">
            <div class="card-header bg-white font-weight-bold text-danger">Skipped rows: fix these in your file and upload them again</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th style="width:80px;">Row</th><th>Name</th><th>Problem</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($report['skipped'], 0, 300) as $item)
                            <tr><td>{{ $item['row'] }}</td><td>{{ $item['name'] }}</td><td>{{ $item['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($report['skipped']) > 300)
                    <div class="p-2 small text-muted">Showing the first 300 of {{ count($report['skipped']) }} skipped rows.</div>
                @endif
            </div>
        </div>
    @endif

    @if (count($report['warnings']))
        <div class="card mb-3">
            <div class="card-header bg-white font-weight-bold">Warnings: these rows were added, but something was ignored</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th style="width:80px;">Row</th><th>Name</th><th>Note</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($report['warnings'], 0, 300) as $item)
                            <tr><td>{{ $item['row'] }}</td><td>{{ $item['name'] }}</td><td>{{ $item['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
