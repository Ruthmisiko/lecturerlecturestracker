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
        .box { border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 14px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .grid .k { color: #666; width: 22%; }
        .pay { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .pay th { background: #28a745; color: #fff; padding: 7px; text-align: left; }
        .pay td { padding: 7px; border-bottom: 1px solid #e5e5e5; }
        .r, .pay th.r { text-align: right; }
        .ded { color: #c0392b; }
        .net td { font-size: 14px; font-weight: bold; background: #e9f6ec; border-top: 2px solid #28a745; color: #1e7e34; }
        h4 { margin: 0 0 6px; font-size: 12px; color: #1e7e34; }
        .sign { width: 100%; margin-top: 40px; }
        .sign td { width: 50%; padding-top: 28px; }
        .line { border-top: 1px solid #555; width: 80%; padding-top: 4px; }
        .foot { margin-top: 24px; font-size: 9px; color: #888; text-align: center; }
    </style>
</head>
<body>
    @php $n = fn ($v) => number_format($v, 2); @endphp

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
                <td class="k">Pay Period</td><td>{{ $period }}</td>
            </tr>
            <tr>
                <td class="k">Department</td><td>{{ $lecturer->department->name ?? '—' }}</td>
                <td class="k">Date Issued</td><td>{{ now()->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="k">ID Number</td><td>{{ $lecturer->id_number ?: '—' }}</td>
                <td class="k">KRA PIN</td><td>{{ $lecturer->kra_pin ?: '—' }}</td>
            </tr>
            <tr>
                <td class="k">Email</td><td>{{ $lecturer->email ?: '—' }}</td>
                <td class="k">Phone</td><td>{{ $lecturer->phone ?: '—' }}</td>
            </tr>
        </table>
    </div>

    <table class="pay">
        <thead>
            <tr><th>Description</th><th class="r">Details</th><th class="r">Amount (KES)</th></tr>
        </thead>
        <tbody>
            <tr><td>Lecture sessions</td><td class="r">{{ $row->sessions }}</td><td></td></tr>
            <tr><td>Hours taught</td><td class="r">{{ $n($row->hours) }} hrs × {{ $n($row->rate) }}</td><td class="r"></td></tr>
            <tr><td><strong>Gross Pay</strong></td><td></td><td class="r"><strong>{{ $n($row->gross) }}</strong></td></tr>
            <tr><td class="ded">Less: Tax</td><td class="r">{{ rtrim(rtrim(number_format($row->tax_rate, 2), '0'), '.') }}% of gross</td><td class="r ded">({{ $n($row->tax) }})</td></tr>
            <tr><td class="ded">Less: Housing Levy</td><td class="r">{{ rtrim(rtrim(number_format($row->housing_levy_rate, 2), '0'), '.') }}% of gross</td><td class="r ded">({{ $n($row->housing) }})</td></tr>
            <tr><td>Total Deductions</td><td></td><td class="r ded">({{ $n($row->tax + $row->housing) }})</td></tr>
            <tr class="net"><td>NET PAY</td><td></td><td class="r">KES {{ $n($row->net) }}</td></tr>
        </tbody>
    </table>

    @if (!empty($row->units))
        <div class="box">
            <h4>Hours by Unit</h4>
            <table class="grid">
                @foreach ($row->units as $unit => $hours)
                    <tr><td>{{ $unit }}</td><td class="r">{{ $n($hours) }} hrs</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    <table class="sign">
        <tr>
            <td><div class="line">Prepared by</div></td>
            <td><div class="line">Approved by</div></td>
        </tr>
    </table>

    <div class="foot">Computer-generated payslip · {{ now()->format('d M Y, H:i') }}</div>
</body>
</html>
