@extends('layouts.app')

@push('styles')
<style>
    .schedule-table { min-width: 1080px; }
    .schedule-table th { white-space: nowrap; font-size: 13px; }
    .schedule-table td { vertical-align: middle; padding: 4px 6px; }
    .schedule-table .form-control { height: 32px; padding: 4px 8px; font-size: 14px; }
    .schedule-table .sno { width: 48px; text-align: center; color: #6c757d; }
    .schedule-table .amount-input { text-align: right; }
    .schedule-table tfoot td { font-weight: 700; font-size: 15px; }
    .payable-btn { font-size: 12px; padding: 1px 8px; margin-top: 3px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .payable-menu { min-width: 270px; }
    .sign-card label { font-size: 13px; color: #6c757d; margin-bottom: 2px; }
    .row-busy .amount-input { opacity: .5; }
</style>
@endpush

@section('content')
@php
    $isEdit = $schedule->exists;
    $rows   = collect(old('items', $items))->values();
@endphp

<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <h1 class="font-weight-bold mb-0">{{ $isEdit ? 'Edit Payment Schedule' : 'New Payment Schedule' }}</h1>
        <a href="{{ route('payment-schedules.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Payment Schedules</a>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <form method="POST" action="{{ $isEdit ? route('payment-schedules.update', $schedule->id) : route('payment-schedules.store') }}" id="scheduleForm" autocomplete="off">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- Details --}}
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               value="{{ old('title', $schedule->title) }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Date</label>
                        <input type="date" name="schedule_date" class="form-control" required
                               value="{{ old('schedule_date', $schedule->schedule_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Hours from <small class="text-muted">(optional)</small></label>
                        <input type="date" name="period_from" id="periodFrom" class="form-control recalc"
                               value="{{ old('period_from', $schedule->period_from?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Hours to <small class="text-muted">(optional)</small></label>
                        <input type="date" name="period_to" id="periodTo" class="form-control recalc"
                               value="{{ old('period_to', $schedule->period_to?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
                    <button type="button" class="btn btn-outline-success" id="recalcBtn">
                        <i class="fas fa-sync-alt mr-1"></i> Recalculate all amounts
                    </button>
                    <small class="text-muted" id="recalcNote">Amount = the lecturer's net pay for the hours chosen. Use <strong>Payable hrs</strong> under each amount to pick which statuses count for that lecturer. You can still type any amount.</small>
                </div>
            </div>
        </div>

        {{-- Rows --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
                <strong><i class="fas fa-university mr-1 text-success"></i> Bank Account Details</strong>
                <div class="d-flex flex-wrap" style="gap:6px;">
                    <select id="addLecturer" class="form-control form-control-sm" style="width: 240px;">
                        <option value="">Add lecturer…</option>
                        @foreach ($lecturers as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-success" id="addLecturerBtn"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addBlankBtn">Blank row</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeZeroBtn" title="Remove rows whose amount is 0">Remove 0 amounts</button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="overflow: visible;">
                <table class="table table-bordered table-striped mb-0 schedule-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="sno">S/NO</th>
                            <th>NAME</th>
                            <th>ACCOUNT NO.</th>
                            <th class="text-right" style="width:190px;">AMOUNT</th>
                            <th>BANK</th>
                            <th>BRANCH</th>
                            <th style="width:140px;">ID NO.</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>TOTAL</td>
                            <td></td>
                            <td class="text-right" id="total">0.00</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Signatories --}}
        <div class="row">
            <div class="col-md-6">
                <div class="card sign-card">
                    <div class="card-header font-weight-bold">Authorized By</div>
                    <div class="card-body">
                        <label>Name</label>
                        <input type="text" name="authorized_by_name" class="form-control mb-2" maxlength="255"
                               value="{{ old('authorized_by_name', $schedule->authorized_by_name) }}" placeholder="e.g. ABIUD MISIKO">
                        <label>Position</label>
                        <input type="text" name="authorized_by_position" class="form-control" maxlength="255"
                               value="{{ old('authorized_by_position', $schedule->authorized_by_position) }}" placeholder="e.g. ACCOUNTANT I/C">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card sign-card">
                    <div class="card-header font-weight-bold">Approved By</div>
                    <div class="card-body">
                        <label>Name</label>
                        <input type="text" name="approved_by_name" class="form-control mb-2" maxlength="255"
                               value="{{ old('approved_by_name', $schedule->approved_by_name) }}" placeholder="e.g. BEN SIMIYU">
                        <label>Position</label>
                        <input type="text" name="approved_by_position" class="form-control" maxlength="255"
                               value="{{ old('approved_by_position', $schedule->approved_by_position) }}" placeholder="e.g. PRINCIPAL">
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button class="btn btn-success btn-lg"><i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Update Schedule' : 'Create Schedule' }}</button>
            <a href="{{ route('payment-schedules.index') }}" class="btn btn-default btn-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var STATUSES   = @json(\App\Services\LecturerPayroll::STATUSES);
    var ALL        = Object.keys(STATUSES);
    var lecturers  = @json($lecturers->keyBy('id'));
    var initial    = @json($rows);
    var amountsUrl = @json(route('payment-schedules.amounts'));
    var cache      = {};   // "statusKey" => { lecturerId: net }
    var rowsEl     = document.getElementById('rows');
    var nextIndex  = 0;

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function money(v) { return Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function rowStatuses(tr) {
        return Array.prototype.map.call(tr.querySelectorAll('.payable-status:checked'), function (b) { return b.value; });
    }
    function statusKey(list) { return list.length === 0 || list.length === ALL.length ? 'all' : list.slice().sort().join(','); }
    function statusText(list) {
        return list.length === 0 || list.length === ALL.length ? 'All' : list.map(function (s) { return STATUSES[s]; }).join(', ');
    }

    /** Net pay for every lecturer with the given statuses (cached per period + status set). */
    function fetchAmounts(list) {
        var params = new URLSearchParams();
        var from = document.getElementById('periodFrom').value, to = document.getElementById('periodTo').value;
        if (from) params.append('period_from', from);
        if (to) params.append('period_to', to);
        if (statusKey(list) !== 'all') list.forEach(function (s) { params.append('statuses[]', s); });

        var key = params.toString();
        if (!cache[key]) {
            cache[key] = fetch(amountsUrl + '?' + key, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                .catch(function (e) { delete cache[key]; throw e; });
        }
        return cache[key];
    }

    function renumber() {
        var total = 0;
        Array.prototype.forEach.call(rowsEl.children, function (tr, i) {
            tr.querySelector('.sno').textContent = i + 1;
            total += parseFloat(tr.querySelector('.amount-input').value) || 0;
        });
        document.getElementById('total').textContent = money(total);
    }

    /** Recalculate one row's amount from its own payable statuses. */
    function refreshRow(tr) {
        var id = tr.dataset.lecturer;
        if (!id) return Promise.resolve();
        tr.classList.add('row-busy');
        return fetchAmounts(rowStatuses(tr)).then(function (data) {
            tr.querySelector('.amount-input').value = Number(data[id] || 0).toFixed(2);
            renumber();
        }).catch(function () {
            document.getElementById('recalcNote').textContent = 'Could not refresh amounts. Check your connection and try again.';
        }).finally(function () { tr.classList.remove('row-busy'); });
    }

    function payableDropdown(i, selected) {
        var on = selected && selected.length ? selected : ALL;
        var boxes = ALL.map(function (s) {
            var id = 'st_' + i + '_' + s;
            return '<div class="form-check mb-1">' +
                '<input class="form-check-input payable-status" type="checkbox" name="items[' + i + '][statuses][]" value="' + s + '" id="' + id + '"' + (on.indexOf(s) !== -1 ? ' checked' : '') + '>' +
                '<label class="form-check-label" for="' + id + '">' + esc(STATUSES[s]) + '</label></div>';
        }).join('');
        return '<div class="dropdown">' +
            '<button type="button" class="btn btn-outline-secondary payable-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Which statuses count for this lecturer">' +
                '<i class="fas fa-filter mr-1"></i>Payable hrs: <strong class="payable-label">' + esc(statusText(on)) + '</strong></button>' +
            '<div class="dropdown-menu dropdown-menu-right p-3 text-left payable-menu" onclick="event.stopPropagation()">' +
                '<h6 class="dropdown-header px-0">Count hours with status</h6>' + boxes +
                '<div class="d-flex justify-content-between mt-2">' +
                    '<button type="button" class="btn btn-sm btn-link px-0 payable-all">Select all</button>' +
                    '<button type="button" class="btn btn-sm btn-success payable-apply">Apply</button>' +
                '</div>' +
            '</div></div>';
    }

    function addRow(item) {
        var i = nextIndex++;
        var f = function (name, value, extra) {
            return '<input type="text" name="items[' + i + '][' + name + ']" class="form-control" value="' + esc(value) + '"' + (extra || '') + '>';
        };
        var tr = document.createElement('tr');
        tr.dataset.lecturer = item.lecturer_id || '';
        tr.innerHTML =
            '<td class="sno"></td>' +
            '<td><input type="hidden" name="items[' + i + '][lecturer_id]" value="' + esc(item.lecturer_id || '') + '">' + f('name', item.name, ' required') + '</td>' +
            '<td>' + f('account_number', item.account_number) + '</td>' +
            '<td class="text-right">' +
                '<input type="number" step="0.01" min="0" name="items[' + i + '][amount]" class="form-control amount-input" required value="' + Number(item.amount || 0).toFixed(2) + '">' +
                (item.lecturer_id ? payableDropdown(i, item.statuses) : '') +
            '</td>' +
            '<td>' + f('bank', item.bank) + '</td>' +
            '<td>' + f('branch', item.branch) + '</td>' +
            '<td>' + f('id_number', item.id_number) + '</td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove"><i class="fas fa-times"></i></button></td>';
        rowsEl.appendChild(tr);
        renumber();
        return tr;
    }

    // Row actions (remove, per-row payable statuses)
    rowsEl.addEventListener('click', function (e) {
        var tr = e.target.closest('tr');
        if (e.target.closest('.remove-row')) {
            tr.remove();
            renumber();
        } else if (e.target.closest('.payable-all')) {
            tr.querySelectorAll('.payable-status').forEach(function (b) { b.checked = true; });
        } else if (e.target.closest('.payable-apply')) {
            var list = rowStatuses(tr);
            if (!list.length) { alert('Tick at least one status.'); return; }
            tr.querySelector('.payable-label').textContent = statusText(list);
            $(tr).find('[data-toggle="dropdown"]').dropdown('toggle');
            refreshRow(tr);
        }
    });
    rowsEl.addEventListener('input', function (e) {
        if (e.target.classList.contains('amount-input')) renumber();
    });

    document.getElementById('addLecturerBtn').addEventListener('click', function () {
        var l = lecturers[document.getElementById('addLecturer').value];
        if (!l) return;
        var tr = addRow({ lecturer_id: l.id, name: l.name, account_number: l.account_number, bank: l.bank_name,
                          branch: l.bank_branch, id_number: l.id_number, amount: 0, statuses: null });
        refreshRow(tr);
        tr.querySelector('input[name$="[account_number]"]').focus();
    });

    document.getElementById('addBlankBtn').addEventListener('click', function () {
        addRow({ name: '', amount: 0 }).querySelector('input[name$="[name]"]').focus();
    });

    document.getElementById('removeZeroBtn').addEventListener('click', function () {
        Array.prototype.slice.call(rowsEl.children).forEach(function (tr) {
            if (!(parseFloat(tr.querySelector('.amount-input').value) > 0)) tr.remove();
        });
        renumber();
    });

    function refreshAll() {
        cache = {};
        var note = document.getElementById('recalcNote');
        Promise.all(Array.prototype.map.call(rowsEl.children, refreshRow)).then(function () { note.textContent = 'Amounts updated.'; });
    }
    document.getElementById('recalcBtn').addEventListener('click', refreshAll);
    document.querySelectorAll('.recalc').forEach(function (el) { el.addEventListener('change', refreshAll); });

    document.getElementById('scheduleForm').addEventListener('submit', function (e) {
        if (!rowsEl.children.length) {
            e.preventDefault();
            alert('Add at least one lecturer to the schedule.');
        }
    });

    initial.forEach(addRow);
})();
</script>
@endpush
