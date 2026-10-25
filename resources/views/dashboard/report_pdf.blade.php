<!DOCTYPE html>
<html>
@php $theme = \App\Support\Theme::current(); $n = fn ($v) => number_format($v, 2); @endphp
<head>
    <meta charset="UTF-8">
    <title>Lecturer Payment Report</title>
    <style>
        @page { margin: 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #111; }
        h2 { margin: 0 0 2px; color: {{ $theme->primary }}; font-size: 14px; }
        .meta { color: #555; margin-bottom: 10px; }
        h3 { font-size: 11px; margin: 12px 0 4px; text-transform: uppercase; }
        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th { background: {{ $theme->primary }}; color: {{ $theme->primaryText() }}; padding: 4px; text-align: left; font-size: 7.5px; }
        table.rep td { padding: 3px 4px; border: 1px solid #ccc; }
        table.rep tr:nth-child(even) td { background: #f6f6f6; }
        table.rep tfoot td { font-weight: bold; background: {{ $theme->primarySoft() }}; }
        .r { text-align: right; white-space: nowrap; }
        table.sum { border-collapse: collapse; margin-top: 6px; }
        table.sum td { padding: 2px 10px; background: #e2e3e5; font-weight: bold; border: 1px solid #fff; }
        table.sum td.r { background: #f1f2f3; min-width: 90px; }
        .grand { margin-top: 12px; font-weight: bold; font-size: 9px; }
    </style>
</head>
<body>
    <h2>Lecturer Payment Report</h2>
    <div class="meta">Period: {{ $period }} &nbsp;|&nbsp; {{ $department->name ?? 'All departments' }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; Amounts in KES</div>

    @foreach ($sections as $section)
        <h3>{{ $section->name }}</h3>
        <table class="rep">
            <thead>
                <tr>
                    <th>SNO</th>
                    <th>EMPLOYEE PIN</th>
                    <th>NAME</th>
                    <th class="r">HOURS WORKED</th>
                    <th class="r">RATE</th>
                    <th class="r">GROSS AMOUNT</th>
                    <th class="r">HOUSE LEVY</th>
                    <th class="r">TAX DEDUCTED</th>
                    <th class="r">NET AMOUNT</th>
                    <th>DEPARTMENT</th>
                    <th>PERIOD TAUGHT</th>
                    <th>PV NO VCH NO</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($section->rows as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r->kra_pin }}</td>
                        <td>{{ $r->name }}</td>
                        <td class="r">{{ $n($r->hours) }}</td>
                        <td class="r">{{ $n($r->rate) }}</td>
                        <td class="r">{{ $n($r->gross) }}</td>
                        <td class="r">{{ $n($r->housing) }}</td>
                        <td class="r">{{ $n($r->tax) }}</td>
                        <td class="r">{{ $n($r->net) }}</td>
                        <td>{{ $r->department }}</td>
                        <td>{{ $r->period_taught }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td class="r">{{ $n($section->totals->hours) }}</td>
                    <td></td>
                    <td class="r">{{ $n($section->totals->gross) }}</td>
                    <td class="r">{{ $n($section->totals->housing) }}</td>
                    <td class="r">{{ $n($section->totals->tax) }}</td>
                    <td class="r">{{ $n($section->totals->net) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        <table class="sum">
            <tr><td>EMPLOYEE AHL</td><td class="r">{{ $n($section->totals->housing) }}</td></tr>
            <tr><td>EMPLOYER AHL</td><td class="r">{{ $n($section->totals->employer_levy) }}</td></tr>
            <tr><td>TOTAL AHL</td><td class="r">{{ $n($section->totals->housing + $section->totals->employer_levy) }}</td></tr>
            <tr><td>TAX DEDUCTED</td><td class="r">{{ $n($section->totals->tax) }}</td></tr>
        </table>
    @endforeach

    @if ($sections->count() > 1)
        <div class="grand">
            GRAND TOTAL (ALL DEPARTMENTS) — Hours: {{ $n($totals->hours) }} &nbsp;|&nbsp; Gross: {{ $n($totals->gross) }}
            &nbsp;|&nbsp; House levy: {{ $n($totals->housing) }} &nbsp;|&nbsp; Tax: {{ $n($totals->tax) }} &nbsp;|&nbsp; Net: {{ $n($totals->net) }}
        </div>
    @endif
</body>
</html>
