@extends('layouts.app')

@push('styles')
<style>
    .schedule-table { min-width: 980px; }
    .schedule-table th { white-space: nowrap; font-size: 13px; }
    .schedule-table td { vertical-align: middle; padding: 4px 6px; }
    .schedule-table .form-control { height: 32px; padding: 4px 8px; font-size: 14px; }
    .schedule-table .sno { width: 48px; text-align: center; color: #6c757d; }
    .schedule-table .amount input { text-align: right; }
    .schedule-table tfoot td { font-weight: 700; font-size: 15px; }
    .sign-card label { font-size: 13px; color: #6c757d; margin-bottom: 2px; }
</style>
@endpush

@section('content')
@php
    $statusOptions = \App\Services\LecturerPayroll::STATUSES;
    $checked = old('statuses', $schedule->statuses ?? array_keys($statusOptions)) ?: array_keys($statusOptions);
    $isEdit = $schedule->exists;
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
                    {{-- Payable hours: which clash statuses count towards the amount --}}
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter mr-1"></i> Payable hrs: <strong id="statusLabel">All</strong>
                        </button>
                        <div class="dropdown-menu p-3" style="min-width: 270px;" onclick="event.stopPropagation()">
                            <h6 class="dropdown-header px-0">Count hours with status</h6>
                            @foreach ($statusOptions as $key => $label)
                                <div class="form-check mb-1">
                                    <input class="form-check-input payable-status recalc" type="checkbox" name="statuses[]" value="{{ $key }}"
                                           id="st_{{ $key }}" data-label="{{ $label }}" {{ in_array($key, $checked) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="st_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-success" id="recalcBtn">
                        <i class="fas fa-sync-alt mr-1"></i> Recalculate amounts
                    </button>
                    <small class="text-muted" id="recalcNote">Amount = each lecturer's net pay for the hours and statuses chosen. You can still edit any amount.</small>
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
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0 schedule-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="sno">S/NO</th>
                            <th>NAME</th>
                            <th>ACCOUNT NO.</th>
                            <th class="text-right" style="width:150px;">AMOUNT</th>
                            <th>BANK</th>
                            <th>BRANCH</th>
                            <th style="width:140px;">ID NO.</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                        @foreach (old('items', $items) as $i => $item)
                            <tr data-lecturer="{{ $item['lecturer_id'] ?? '' }}">
                                <td class="sno"></td>
                                <td>
                                    <input type="hidden" name="items[{{ $i }}][lecturer_id]" value="{{ $item['lecturer_id'] ?? '' }}">
                                    <input type="text" name="items[{{ $i }}][name]" class="form-control" required value="{{ $item['name'] ?? '' }}">
                                </td>
                                <td><input type="text" name="items[{{ $i }}][account_number]" class="form-control" value="{{ $item['account_number'] ?? '' }}"></td>
                                <td class="amount"><input type="number" step="0.01" min="0" name="items[{{ $i }}][amount]" class="form-control amount-input" required
                                                          value="{{ number_format((float) ($item['amount'] ?? 0), 2, '.', '') }}"></td>
                                <td><input type="text" name="items[{{ $i }}][bank]" class="form-control" value="{{ $item['bank'] ?? '' }}"></td>
                                <td><input type="text" name="items[{{ $i }}][branch]" class="form-control" value="{{ $item['branch'] ?? '' }}"></td>
                                <td><input type="text" name="items[{{ $i }}][id_number]" class="form-control" value="{{ $item['id_number'] ?? '' }}"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
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
    var lecturers = @json($lecturers->keyBy('id'));
    var amountsUrl = @json(route('payment-schedules.amounts'));
    var amounts = {};
    var rowsEl = document.getElementById('rows');
    var nextIndex = rowsEl.children.length;

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function money(v) { return Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function renumber() {
        var total = 0;
        Array.prototype.forEach.call(rowsEl.children, function (tr, i) {
            tr.querySelector('.sno').textContent = i + 1;
            total += parseFloat(tr.querySelector('.amount-input').value) || 0;
        });
        document.getElementById('total').textContent = money(total);
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
            '<td class="amount"><input type="number" step="0.01" min="0" name="items[' + i + '][amount]" class="form-control amount-input" required value="' + Number(item.amount || 0).toFixed(2) + '"></td>' +
            '<td>' + f('bank', item.bank) + '</td>' +
            '<td>' + f('branch', item.branch) + '</td>' +
            '<td>' + f('id_number', item.id_number) + '</td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove"><i class="fas fa-times"></i></button></td>';
        rowsEl.appendChild(tr);
        renumber();
        return tr;
    }

    rowsEl.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
            renumber();
        }
    });
    rowsEl.addEventListener('input', function (e) {
        if (e.target.classList.contains('amount-input')) renumber();
    });

    document.getElementById('addLecturerBtn').addEventListener('click', function () {
        var id = document.getElementById('addLecturer').value;
        var l = lecturers[id];
        if (!l) return;
        addRow({ lecturer_id: l.id, name: l.name, account_number: l.account_number, bank: l.bank_name,
                 branch: l.bank_branch, id_number: l.id_number, amount: amounts[l.id] || 0 })
            .querySelector('input[name$="[account_number]"]').focus();
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

    function statusLabel() {
        var boxes = document.querySelectorAll('.payable-status');
        var on = Array.prototype.filter.call(boxes, function (b) { return b.checked; });
        document.getElementById('statusLabel').textContent =
            on.length === boxes.length || on.length === 0 ? 'All' : on.map(function (b) { return b.dataset.label; }).join(', ');
    }

    // Fetch net pay for the chosen period/statuses and update every lecturer row's amount.
    function recalc(updateRows) {
        var params = new URLSearchParams();
        ['periodFrom', 'periodTo'].forEach(function (id) {
            var v = document.getElementById(id).value;
            if (v) params.append(id === 'periodFrom' ? 'period_from' : 'period_to', v);
        });
        document.querySelectorAll('.payable-status:checked').forEach(function (b) { params.append('statuses[]', b.value); });

        var note = document.getElementById('recalcNote');
        fetch(amountsUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                amounts = data;
                if (!updateRows) return;
                Array.prototype.forEach.call(rowsEl.children, function (tr) {
                    var id = tr.dataset.lecturer;
                    if (id && data[id] !== undefined) tr.querySelector('.amount-input').value = Number(data[id]).toFixed(2);
                });
                renumber();
                note.textContent = 'Amounts updated.';
            })
            .catch(function () { note.textContent = 'Could not refresh amounts. Check your connection and try again.'; });
    }

    document.getElementById('recalcBtn').addEventListener('click', function () { recalc(true); });
    document.querySelectorAll('.recalc').forEach(function (el) {
        el.addEventListener('change', function () { statusLabel(); recalc(true); });
    });

    document.getElementById('scheduleForm').addEventListener('submit', function (e) {
        if (!rowsEl.children.length) {
            e.preventDefault();
            alert('Add at least one lecturer to the schedule.');
        }
    });

    statusLabel();
    renumber();
    recalc(false); // load amounts for the "Add lecturer" button without touching existing rows
})();
</script>
@endpush
