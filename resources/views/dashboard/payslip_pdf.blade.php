<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $lecturer->name }}</title>
    <style>
        /* Half-width A4 slip (105 × 210 mm) */
        @page { margin: 8mm 7mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #222; margin: 0; }
        .header { border-bottom: 2px solid #28a745; padding-bottom: 6px; margin-bottom: 10px; }
        .header table { width: 100%; border-collapse: collapse; }
        .org { font-size: 12px; font-weight: bold; color: #1e7e34; }
        .title { font-size: 10px; font-weight: bold; text-align: right; letter-spacing: 1.5px; vertical-align: top; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; border-radius: 3px; padding: 5px 6px; margin-bottom: 10px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 2px 0; vertical-align: top; }
        .grid .k { color: #666; width: 36%; }
        .pay { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
        .pay td { padding: 5px 0; border-bottom: 1px dashed #e0e0e0; }
        .pay td.v { text-align: right; white-space: nowrap; }
        .pay .note { color: #888; font-size: 7.5px; font-weight: normal; }
        .pay .bold td { font-weight: bold; }
        .pay .ded td { color: #c0392b; }
        .pay .net td { font-size: 11px; font-weight: bold; color: #000; border-bottom: 0; padding-top: 7px; }
        h4 { margin: 0 0 3px; font-size: 9px; }
        .units { width: 100%; border-collapse: collapse; font-size: 7.5px; }
        .units td { padding: 1px 0; }
        .units td.v { text-align: right; white-space: nowrap; }
        .sign { width: 100%; margin-top: 8px; border-collapse: collapse; }
        .sign td { width: 50%; padding-top: 26px; vertical-align: bottom; font-size: 7.5px; }
        .line { border-top: 1px solid #555; width: 90%; padding-top: 2px; }
    </style>
</head>
<body>
    @php
        $money = fn ($v) => 'KES ' . number_format($v, 4);
        $pct   = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.') . '%';
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="org">{{ $lecturer->institution->name ?? 'Lecturer Tracker' }}</div>
                    <div class="muted">Part-time Lecturer Payment</div>
                </td>
                <td class="title">
                    PAYSLIP<br>
                    <span class="muted" style="font-size:7px;letter-spacing:0;font-weight:normal;">No. {{ $slipNo }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table class="grid">
            <tr><td class="k">Lecturer</td><td><strong>{{ $lecturer->name }}</strong></td></tr>
            <tr><td class="k">Department</td><td>{{ $lecturer->department->name ?? '—' }}</td></tr>
            <tr><td class="k">KRA PIN</td><td>{{ $lecturer->kra_pin ?: '—' }}</td></tr>
            <tr><td class="k">Phone</td><td>{{ $lecturer->phone ?: '—' }}</td></tr>
        </table>
    </div>

    <table class="pay">
        <tr><td>Lecture sessions</td><td class="v">{{ $row->sessions }}</td></tr>
        <tr><td>Total hours</td><td class="v">{{ number_format($row->hours, 4) }} hrs</td></tr>
        <tr><td>Rate per hour</td><td class="v">{{ $money($row->rate) }}</td></tr>
        <tr class="bold"><td>Gross amount <span class="note">(hours × rate)</span></td><td class="v">{{ $money($row->gross) }}</td></tr>
        <tr class="ded"><td>Tax ({{ $pct($row->tax_rate) }})</td><td class="v">− {{ $money($row->tax) }}</td></tr>
        <tr class="ded"><td>Housing levy ({{ $pct($row->housing_levy_rate) }})</td><td class="v">− {{ $money($row->housing) }}</td></tr>
        <tr class="net"><td>Net pay</td><td class="v">{{ $money($row->net) }}</td></tr>
    </table>

    @if ($statuses)
        <p class="muted" style="font-size:7px;margin:-5px 0 8px;">
            Payable hours counted: {{ \App\Http\Controllers\DashboardController::statusLabel($statuses) }}
        </p>
    @endif

    @if (!empty($row->units))
        <h4>Hours by unit</h4>
        <table class="units">
            @foreach ($row->units as $unit => $hours)
                <tr><td>{{ $unit }}</td><td class="v">{{ number_format($hours, 4) }} hrs</td></tr>
            @endforeach
        </table>
    @endif

    <table class="sign">
        <tr>
            <td><div class="line">HOD Confirmation</div></td>
            <td></td>
        </tr>
        <tr>
            <td><div class="line">Authorised and Prepared by</div></td>
            <td><div class="line">Approved by</div></td>
        </tr>
    </table>
</body>
</html>
