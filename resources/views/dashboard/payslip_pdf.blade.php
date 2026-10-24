<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $lecturer->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; }
        .header { border-bottom: 3px solid #28a745; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .org { font-size: 18px; font-weight: bold; color: #1e7e34; }
        .title { font-size: 15px; font-weight: bold; text-align: right; letter-spacing: 2px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 16px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .grid .k { color: #666; width: 22%; }
        .pay { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px; }
        .pay td { padding: 9px 2px; border-bottom: 1px dashed #e0e0e0; }
        .pay td.v { text-align: right; }
        .pay .note { color: #888; font-size: 10px; font-weight: normal; }
        .pay .bold td { font-weight: bold; }
        .pay .ded td { color: #c0392b; }
        .pay .net td { font-size: 15px; font-weight: bold; color: #000; border-bottom: 0; padding-top: 12px; }
        h4 { margin: 0 0 6px; font-size: 12px; }
        .units { width: 100%; border-collapse: collapse; font-size: 10px; }
        .units td { padding: 2px 2px; }
        .units td.v { text-align: right; }
        .sign { width: 100%; margin-top: 20px; }
        .sign td { width: 50%; padding-top: 36px; vertical-align: bottom; }
        .line { border-top: 1px solid #555; width: 80%; padding-top: 4px; }
    </style>
</head>
<body>
    @php
        $money = fn ($v) => 'KES ' . number_format($v, 2);
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
                    <span class="muted" style="font-size:10px;letter-spacing:0;font-weight:normal;">No. {{ $slipNo }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table class="grid">
            <tr>
                <td class="k">Lecturer</td><td><strong>{{ $lecturer->name }}</strong></td>
                <td class="k">Department</td><td>{{ $lecturer->department->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="k">KRA PIN</td><td>{{ $lecturer->kra_pin ?: '—' }}</td>
                <td class="k">Phone</td><td>{{ $lecturer->phone ?: '—' }}</td>
            </tr>
        </table>
    </div>

    <table class="pay">
        <tr><td>Lecture sessions</td><td class="v">{{ $row->sessions }}</td></tr>
        <tr><td>Total hours</td><td class="v">{{ number_format($row->hours, 2) }} hrs</td></tr>
        <tr><td>Rate per hour</td><td class="v">{{ $money($row->rate) }}</td></tr>
        <tr class="bold"><td>Gross amount <span class="note">(hours × rate)</span></td><td class="v">{{ $money($row->gross) }}</td></tr>
        <tr class="ded"><td>Tax ({{ $pct($row->tax_rate) }})</td><td class="v">− {{ $money($row->tax) }}</td></tr>
        <tr class="ded"><td>Housing levy ({{ $pct($row->housing_levy_rate) }})</td><td class="v">− {{ $money($row->housing) }}</td></tr>
        <tr class="net"><td>Net pay</td><td class="v">{{ $money($row->net) }}</td></tr>
    </table>

    @if ($statuses)
        <p class="muted" style="font-size:10px;margin:-8px 0 14px;">
            Payable hours counted: {{ \App\Http\Controllers\DashboardController::statusLabel($statuses) }}
        </p>
    @endif

    @if (!empty($row->units))
        <h4>Hours by unit</h4>
        <table class="units">
            @foreach ($row->units as $unit => $hours)
                <tr><td>{{ $unit }}</td><td class="v">{{ number_format($hours, 1) }} hrs</td></tr>
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
