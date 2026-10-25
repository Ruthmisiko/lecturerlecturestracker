@extends('owner.layout')
@section('title', $invoice->number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
    <a href="{{ route('owner.invoices.index') }}" class="btn btn-light border"><i class="fas fa-arrow-left mr-1"></i> Invoices</a>
    <div class="d-flex flex-wrap" style="gap:6px;">
        <form method="POST" action="{{ route('owner.invoices.status', $invoice->id) }}" class="d-flex" style="gap:6px;">
            @csrf
            <select name="status" class="form-control form-control-sm" style="width:140px;">
                @foreach (\App\Models\Invoice::STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ $invoice->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-navy">Update status</button>
        </form>
        <a href="{{ route('owner.invoices.edit', $invoice->id) }}" class="btn btn-sm btn-light border"><i class="far fa-edit mr-1"></i> Edit</a>
        <a href="{{ route('owner.invoices.pdf', $invoice->id) }}" class="btn btn-sm btn-orange"><i class="fas fa-file-pdf mr-1"></i> Download PDF</a>
    </div>
</div>

<style>@include('owner.invoices._styles')</style>
<div class="card">
    <div class="card-body p-4 p-md-5" style="max-width: 900px;">
        @include('owner.invoices._document')
    </div>
</div>
@endsection
