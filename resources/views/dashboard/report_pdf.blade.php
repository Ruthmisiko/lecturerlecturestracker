<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lecturer Payment Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { margin: 0 0 4px; color: #1e7e34; }
        .meta { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #28a745; color: #fff; padding: 6px; text-align: left; }
        td { padding: 5px 6px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        tr:nth-child(even) td { background: #f6f8f7; }
        tfoot td { font-weight: bold; background: #e9f6ec; border-top: 2px solid #28a745; }
    </style>
</head>
<body>
    @php $n = fn ($v) => number_format($v, 2); @endphp
    <h2>Lecturer Payment Report</h2>
    <div class="meta">Period: {{ $period }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; Amounts in KES</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Lecturer</th>
                <th>Department</th>
                <th>KRA PIN</th>
                <th class="r">Total Hrs</th>
                <th class="r">Rate / Hr</th>
                <th class="r">Total Amount</th>
                <th class="r">Tax</th>
                <th class="r">Housing Levy</th>
                <th class="r">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->name }}</td>
                    <td>{{ $r->department }}</td>
                    <td>{{ $r->kra_pin }}</td>
                    <td class="r">{{ $n($r->hours) }}</td>
                    <td class="r">{{ $n($r->rate) }}</td>
                    <td class="r">{{ $n($r->gross) }}</td>
                    <td class="r">{{ $n($r->tax) }}</td>
                    <td class="r">{{ $n($r->housing) }}</td>
                    <td class="r">{{ $n($r->net) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">TOTAL</td>
                <td class="r">{{ $n($totals->hours) }}</td>
                <td></td>
                <td class="r">{{ $n($totals->gross) }}</td>
                <td class="r">{{ $n($totals->tax) }}</td>
                <td class="r">{{ $n($totals->housing) }}</td>
                <td class="r">{{ $n($totals->net) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
