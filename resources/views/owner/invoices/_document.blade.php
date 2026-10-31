{{-- The invoice itself; shared by the web view and the PDF. --}}
@php
    $b = config('billing');
    $money = fn ($v) => $invoice->currency . ' ' . number_format($v, 2);
    $status = $invoice->displayStatus();

    // PDFs read the images from disk; the web page uses URLs.
    $forPdf  = $forPdf ?? false;
    $imgPath = fn ($name) => file_exists(public_path("images/{$name}")) ? ($forPdf ? public_path("images/{$name}") : asset("images/{$name}")) : null;
    $logoSrc = $imgPath('nawema-logo.png');
    $qrSrc   = $imgPath('nawema-qr.png');
@endphp
<div class="inv">
    <table class="inv-head">
        <tr>
            <td>
                <table class="inv-brandrow">
                    <tr>
                        @if ($logoSrc)
                            <td class="inv-logo-cell"><div class="inv-logo-circle"><img src="{{ $logoSrc }}" alt="{{ $b['name'] }}" class="inv-logo"></div></td>
                        @endif
                        <td>
                            <div class="inv-brand">{{ $b['name'] }}</div>
                            <div class="inv-muted">
                                @if ($b['address']){{ $b['address'] }}<br>@endif
                                {{ $b['email'] }}@if ($b['phone']) · {{ $b['phone'] }}@endif<br>
                                {{ $b['website'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="inv-right">
                <div class="inv-title">INVOICE</div>
                <div class="inv-muted">No. <strong>{{ $invoice->number }}</strong></div>
                <div class="inv-status inv-status-{{ strtolower($status) }}">{{ strtoupper($status) }}</div>
            </td>
        </tr>
    </table>

    <table class="inv-meta">
        <tr>
            <td>
                <div class="inv-label">Bill to</div>
                <div class="inv-strong">{{ $invoice->bill_to_name }}</div>
                @if ($invoice->bill_to_email)<div class="inv-muted">{{ $invoice->bill_to_email }}</div>@endif
            </td>
            <td class="inv-right">
                <div><span class="inv-label">Issue date:</span> {{ $invoice->issue_date?->format('d M Y') }}</div>
                @if ($invoice->due_date)<div><span class="inv-label">Due date:</span> {{ $invoice->due_date->format('d M Y') }}</div>@endif
                @if ($invoice->paid_at)<div><span class="inv-label">Paid on:</span> {{ $invoice->paid_at->format('d M Y') }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="inv-items">
        <thead>
            <tr><th>#</th><th>Description</th><th class="inv-right">Qty</th><th class="inv-right">Unit price</th><th class="inv-right">Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="inv-right">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td>
                    <td class="inv-right">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="inv-right">{{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="inv-right">TOTAL</td><td class="inv-right">{{ $money($invoice->total) }}</td></tr>
        </tfoot>
    </table>

    @if ($invoice->notes || $b['payment'])
        <div class="inv-notes">
            @if ($b['payment'])<div><span class="inv-label">Payment details:</span> {{ $b['payment'] }}</div>@endif
            @if ($invoice->notes)<div style="white-space:pre-line;">{{ $invoice->notes }}</div>@endif
        </div>
    @endif

    <div class="inv-foot">
        @if ($qrSrc)
            <img src="{{ $qrSrc }}" alt="{{ $b['website'] }}" class="inv-qr"><br>
            <span class="inv-scan">Scan to visit {{ $b['website'] }}</span><br>
        @endif
        Thank you for using Lecturer Tracker · {{ $b['website'] }}
    </div>
</div>
