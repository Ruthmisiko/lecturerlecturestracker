<!DOCTYPE html>
<html>
@php $theme = \App\Support\Theme::for($lecturer->institution); $logoPath = $theme->logoPath(); $letterheadPath = $lecturer->institution?->assetPath('letterhead'); @endphp
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $lecturer->name }}</title>
    <style>
        /* Half-width A4 slip (105 × 210 mm) */
        @page { margin: 8mm 7mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #222; margin: 0; }
        .header { margin-bottom: 6px; text-align: center; }
        .letterhead { width: 100%; }
        .org { text-align: center; font-size: 12px; font-weight: bold; color: {{ $theme->primary }}; }
        .title { font-size: 12px; font-weight: bold; text-align: center; letter-spacing: 3px; margin: 4px 0 2px; color: {{ $theme->primary }}; }
        .employer { text-align: center; font-size: 8px; color: #444; margin-bottom: 8px; }
        .logo { max-height: 46px; max-width: 120px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; border-radius: 3px; padding: 5px 6px; margin-bottom: 10px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 2px 0; vertical-align: top; }
        .grid .k { color: #666; width: 36%; }
        .pay { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
        .pay td { padding: 5px 0; }
        .pay td.v { text-align: right; white-space: nowrap; }
        .pay .note { color: #888; font-size: 7.5px; font-weight: normal; }
        .pay .bold td { font-weight: bold; }
        .pay .ded td { color: #c0392b; }
        .pay .net td { font-size: 11px; font-weight: bold; color: #000; border-bottom: 0; padding-top: 7px; }
        h4 { margin: 0 0 3px; font-size: 9px; }
        .units { width: 100%; border-collapse: collapse; font-size: 7.5px; }
        .units td { padding: 1px 0; }
        .units td.v { text-align: right; white-space: nowrap; }
        .sign { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .sign td { width: 50%; padding-top: 42px; vertical-align: bottom; font-size: 7.5px; }
        .line { border-top: 1px solid #222; width: 90%; padding-top: 5px; font-weight: bold; font-size: 8px; color: #000; }
        .role { font-size: 7.5px; font-weight: bold; letter-spacing: .5px; margin-top: 4px; color: #444; }
        /* Pinned to the bottom of the page */
        .powered { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e5e5e5; padding-top: 4px; }
        .powered table { margin: 0 auto; border-collapse: collapse; }
        .powered td { vertical-align: middle; padding: 0; }
        .powered img { width: 30px; height: 30px; }
        .powered .txt { padding-left: 6px; font-size: 7px; color: #777; line-height: 1.3; }
        .powered a { color: #2d2a6b; font-weight: bold; font-size: 8px; text-decoration: none; }
    </style>
</head>
<body>
    @php
        $money = fn ($v) => 'KES ' . number_format($v, 1);
        $pct   = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.') . '%';
    @endphp

    {{-- Letterhead (or logo / name when no letterhead is uploaded) --}}
    <div class="header">
        @if ($letterheadPath)
            <img src="{{ $letterheadPath }}" class="letterhead" alt="Letterhead">
        @elseif ($logoPath)
            <div style="text-align:center;"><img src="{{ $logoPath }}" class="logo" alt="Logo"></div>
        @else
            <div class="org">{{ $lecturer->institution->name ?? 'Lecturer Tracker' }}</div>
        @endif
    </div>

    <div class="title">PAYSLIP</div>
    @if ($employerPin = $lecturer->institution->employer_pin ?? null)
        <div class="employer">Employer PIN: <strong>{{ $employerPin }}</strong></div>
    @endif

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
        <tr><td>Total hours</td><td class="v">{{ number_format($row->hours, 1) }} hrs</td></tr>
        <tr><td>Rate per hour</td><td class="v">{{ $money($row->rate) }}</td></tr>
        <tr class="bold"><td>Gross amount</td><td class="v">{{ $money($row->gross) }}</td></tr>
        <tr class="ded"><td>Tax ({{ $pct($row->tax_rate) }})</td><td class="v">− {{ $money($row->tax) }}</td></tr>
        <tr class="ded"><td>Housing levy ({{ $pct($row->housing_levy_rate) }})</td><td class="v">− {{ $money($row->housing) }}</td></tr>
        <tr class="net"><td>Net pay</td><td class="v">{{ $money($row->net) }}</td></tr>
    </table>

    @if (!empty($row->units))
        <h4>Hours by unit</h4>
        <table class="units">
            @foreach ($row->unit_details as $u)
                <tr><td>{{ $u['name'] }}@if ($u['capped']) (max {{ rtrim(rtrim(number_format($u['max'], 2), '0'), '.') }} hrs)@endif</td><td class="v">{{ number_format($u['counted'], 1) }} hrs</td></tr>
            @endforeach
        </table>
    @endif

    <table class="sign">
        <tr>
            <td><div class="line">HOD Confirmation</div></td>
            <td></td>
        </tr>
        <tr>
            <td><div class="line">Authorised and Prepared by<div class="role">ACCOUNTANT</div></div></td>
            <td><div class="line">Approved by<div class="role">PRINCIPAL</div></div></td>
        </tr>
    </table>

    <div class="powered">
        <table>
            <tr>
                @if (file_exists($qr = public_path('images/nawema-qr.png')))
                    <td><img src="{{ $qr }}" alt="nawema.co.ke"></td>
                @endif
                <td class="txt">Powered by<br><a href="https://nawema.co.ke">nawema.co.ke</a></td>
            </tr>
        </table>
    </div>
</body>
</html>
