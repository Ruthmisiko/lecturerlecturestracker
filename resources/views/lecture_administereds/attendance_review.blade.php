@extends('layouts.app')

@push('styles')
<style>
    .review-table td { vertical-align: middle; }
    .review-table .form-control { height: 32px; padding: 4px 8px; }
    .review-table tr.excluded td { opacity: .55; }
    .review-table tr.flagged { background: #fff8e1; }
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="font-weight-bold mb-0">Check the Lectures Read</h1>
        <small class="text-muted">Correct anything misread, untick rows you don't want, then save.</small>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    @foreach ($readErrors ?? [] as $readError)
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{ $readError }}</div>
    @endforeach

    <div class="card">
        <div class="card-body py-2">
            <div class="row small">
                <div class="col-md-4"><span class="text-muted">Lecturer:</span> <strong>{{ $selection['lecturer']->name }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Department:</span> <strong>{{ $selection['department']->name ?? '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Semester:</span> <strong>{{ $selection['semester']->name ?? '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Unit:</span> <strong>{{ $selection['unit']->name ?? '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Class:</span> <strong>{{ $selection['class']->name }}</strong></div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance-upload.store') }}" id="reviewForm">
        @csrf
        <input type="hidden" name="lecturer_id" value="{{ $selection['lecturer']->id }}">
        <input type="hidden" name="department_id" value="{{ $selection['department']->id ?? '' }}">
        <input type="hidden" name="unit_id" value="{{ $selection['unit']->id ?? '' }}">
        <input type="hidden" name="classs_id" value="{{ $selection['class']->id }}">
        <input type="hidden" name="semester_id" value="{{ $selection['semester']->id ?? '' }}">

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ count($rows) }} lecture(s) found</strong>
                <button type="button" class="btn btn-sm btn-outline-success" id="addRow"><i class="fas fa-plus"></i> Add a missed row</button>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered mb-0 review-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:60px;" class="text-center">Save?</th>
                            <th style="width:170px;">Lecture Date</th>
                            <th style="width:130px;">Start</th>
                            <th style="width:130px;">End</th>
                            <th style="width:70px;" class="text-right">Hrs</th>
                            <th>As written</th>
                            <th>Check</th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                        @forelse ($rows as $i => $row)
                            <tr class="{{ $row['notes'] ? 'flagged' : '' }}">
                                <td class="text-center">
                                    <input type="hidden" name="rows[{{ $i }}][include]" value="0">
                                    <input type="checkbox" class="include" name="rows[{{ $i }}][include]" value="1" {{ $row['include'] ? 'checked' : '' }}>
                                </td>
                                <td><input type="date" name="rows[{{ $i }}][date]" class="form-control" value="{{ $row['date'] }}"></td>
                                <td><input type="time" name="rows[{{ $i }}][start_time]" class="form-control start" value="{{ $row['start_time'] }}"></td>
                                <td><input type="time" name="rows[{{ $i }}][end_time]" class="form-control end" value="{{ $row['end_time'] }}"></td>
                                <td class="text-right hrs"></td>
                                <td class="small text-muted">{{ $row['raw'] ?: '—' }}<div class="text-muted" style="font-size:11px;">{{ $row['file'] }}</div></td>
                                <td>
                                    @forelse ($row['notes'] as $note)
                                        <span class="badge badge-warning d-inline-block mb-1">{{ $note }}</span>
                                    @empty
                                        <span class="text-success small"><i class="fas fa-check"></i> OK</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr class="no-rows"><td colspan="7" class="text-center text-muted p-4">No lecture rows could be read. Try a clearer photo, or add the rows yourself.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="4">Selected: <span id="selCount">0</span> lecture(s)</td>
                            <td class="text-right" id="selHours">0</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mb-4">
            <button class="btn btn-success btn-lg"><i class="fas fa-save mr-1"></i> Save Lectures</button>
            <a href="{{ route('attendance-upload.create') }}" class="btn btn-default btn-lg">Start again</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var body = document.getElementById('rows');
    var next = {{ count($rows) }};

    function hours(tr) {
        var s = tr.querySelector('.start').value, e = tr.querySelector('.end').value;
        if (!s || !e) return 0;
        var m = (parseInt(e.substr(0, 2)) * 60 + parseInt(e.substr(3, 2))) - (parseInt(s.substr(0, 2)) * 60 + parseInt(s.substr(3, 2)));
        return m > 0 ? m / 60 : 0;
    }

    function refresh() {
        var count = 0, total = 0;
        body.querySelectorAll('tr:not(.no-rows)').forEach(function (tr) {
            var h = hours(tr), on = tr.querySelector('.include').checked;
            tr.querySelector('.hrs').textContent = h ? h.toFixed(2) : '—';
            tr.classList.toggle('excluded', !on);
            if (on) { count++; total += h; }
        });
        document.getElementById('selCount').textContent = count;
        document.getElementById('selHours').textContent = total.toFixed(2);
    }

    body.addEventListener('input', refresh);
    body.addEventListener('change', refresh);

    document.getElementById('addRow').addEventListener('click', function () {
        var empty = body.querySelector('.no-rows');
        if (empty) empty.remove();
        var i = next++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="text-center"><input type="hidden" name="rows[' + i + '][include]" value="0">' +
                '<input type="checkbox" class="include" name="rows[' + i + '][include]" value="1" checked></td>' +
            '<td><input type="date" name="rows[' + i + '][date]" class="form-control" required></td>' +
            '<td><input type="time" name="rows[' + i + '][start_time]" class="form-control start" required></td>' +
            '<td><input type="time" name="rows[' + i + '][end_time]" class="form-control end" required></td>' +
            '<td class="text-right hrs"></td>' +
            '<td class="small text-muted">Added by hand</td><td></td>';
        body.appendChild(tr);
        tr.querySelector('input[type=date]').focus();
        refresh();
    });

    document.getElementById('reviewForm').addEventListener('submit', function (e) {
        var bad = Array.prototype.some.call(body.querySelectorAll('tr:not(.no-rows)'), function (tr) {
            return tr.querySelector('.include').checked &&
                (!tr.querySelector('input[type=date]').value || !hours(tr));
        });
        if (!body.querySelector('.include:checked')) {
            e.preventDefault(); alert('Tick at least one lecture to save.');
        } else if (bad) {
            e.preventDefault(); alert('Every ticked row needs a date and an end time after the start time.');
        }
    });

    refresh();
})();
</script>
@endpush
