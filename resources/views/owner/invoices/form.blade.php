@extends('owner.layout')
@section('title', $invoice->exists ? 'Edit ' . $invoice->number : 'New Invoice')

@section('content')
@php $items = old('items', $invoice->items ?: []); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
    <h3 class="font-weight-bold mb-0">{{ $invoice->exists ? 'Edit Invoice ' . $invoice->number : 'New Invoice' }}</h3>
    <a href="{{ route('owner.invoices.index') }}" class="btn btn-light border"><i class="fas fa-arrow-left mr-1"></i> Invoices</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $invoice->exists ? route('owner.invoices.update', $invoice->id) : route('owner.invoices.store') }}" id="invoiceForm" autocomplete="off">
    @csrf
    @if ($invoice->exists) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="form-group col-md-3">
                    <label>Invoice number</label>
                    <input type="text" name="number" class="form-control" required maxlength="50" value="{{ old('number', $invoice->number) }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Issue date</label>
                    <input type="date" name="issue_date" class="form-control" required value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d')) }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Due date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach (\App\Models\Invoice::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $invoice->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Institution</label>
                    <select name="institution_id" id="institutionSelect" class="form-control">
                        <option value="">— Not linked —</option>
                        @foreach ($institutions as $i)
                            <option value="{{ $i->id }}" {{ (string) old('institution_id', $invoice->institution_id) === (string) $i->id ? 'selected' : '' }}>{{ $i->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Bill to (name)</label>
                    <input type="text" name="bill_to_name" id="billToName" class="form-control" required maxlength="255" value="{{ old('bill_to_name', $invoice->bill_to_name) }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Bill to (email)</label>
                    <input type="email" name="bill_to_email" id="billToEmail" class="form-control" maxlength="190" value="{{ old('bill_to_email', $invoice->bill_to_email) }}">
                </div>
                <div class="form-group col-md-1">
                    <label>Currency</label>
                    <input type="text" name="currency" class="form-control text-uppercase" required maxlength="3" value="{{ old('currency', $invoice->currency) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Line items</strong>
            <button type="button" class="btn btn-sm btn-navy" id="addItem"><i class="fas fa-plus mr-1"></i> Add item</button>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Description</th><th style="width:120px;">Qty</th><th style="width:170px;">Unit price</th><th style="width:170px;" class="text-right">Amount</th><th style="width:40px;"></th></tr></thead>
                <tbody id="items"></tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-right font-weight-bold">Total</td><td class="text-right font-weight-bold" id="grandTotal">0.00</td><td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <label>Notes <small class="text-muted">(optional — shown on the invoice, e.g. payment instructions)</small></label>
            <textarea name="notes" class="form-control" rows="3" maxlength="5000">{{ old('notes', $invoice->notes) }}</textarea>
        </div>
    </div>

    <button class="btn btn-orange btn-lg"><i class="fas fa-save mr-1"></i> {{ $invoice->exists ? 'Save Invoice' : 'Create Invoice' }}</button>
</form>

@if ($invoice->exists)
    <form method="POST" action="{{ route('owner.invoices.destroy', $invoice->id) }}" class="mt-3" onsubmit="return confirm('Delete invoice {{ $invoice->number }}?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="far fa-trash-alt mr-1"></i> Delete invoice</button>
    </form>
@endif

<script>
(function () {
    var initial = @json(array_values($items));
    var billTo  = @json($billTo);
    var body = document.getElementById('items');
    var next = 0;

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function fmt(v) { return Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function recalc() {
        var total = 0;
        body.querySelectorAll('tr').forEach(function (tr) {
            var amount = (parseFloat(tr.querySelector('.qty').value) || 0) * (parseFloat(tr.querySelector('.price').value) || 0);
            tr.querySelector('.amount').textContent = fmt(amount);
            total += amount;
        });
        document.getElementById('grandTotal').textContent = fmt(total);
    }

    function addRow(item) {
        var i = next++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="items[' + i + '][description]" class="form-control" required maxlength="255" value="' + esc(item.description) + '"></td>' +
            '<td><input type="number" step="0.01" min="0" name="items[' + i + '][quantity]" class="form-control qty" required value="' + (item.quantity ?? 1) + '"></td>' +
            '<td><input type="number" step="0.01" min="0" name="items[' + i + '][unit_price]" class="form-control price" required value="' + (item.unit_price ?? 0) + '"></td>' +
            '<td class="text-right align-middle amount"></td>' +
            '<td class="align-middle"><button type="button" class="btn btn-sm btn-link text-danger remove" title="Remove"><i class="fas fa-times"></i></button></td>';
        tr.querySelector('.remove').addEventListener('click', function () {
            if (body.children.length > 1) { tr.remove(); recalc(); }
        });
        body.appendChild(tr);
        recalc();
        return tr;
    }

    body.addEventListener('input', recalc);
    document.getElementById('addItem').addEventListener('click', function () {
        addRow({ description: '', quantity: 1, unit_price: 0 }).querySelector('input').focus();
    });

    // Picking an institution fills "bill to" (still editable).
    document.getElementById('institutionSelect').addEventListener('change', function () {
        var b = billTo[this.value];
        if (!b) return;
        document.getElementById('billToName').value = b.name;
        document.getElementById('billToEmail').value = b.email;
    });

    (initial.length ? initial : [{ description: '', quantity: 1, unit_price: 0 }]).forEach(addRow);

    var sel = document.getElementById('institutionSelect');
    if (sel.value && !document.getElementById('billToName').value) sel.dispatchEvent(new Event('change'));
})();
</script>
@endsection
