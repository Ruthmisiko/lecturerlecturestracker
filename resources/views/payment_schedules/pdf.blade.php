<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $schedule->title }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; }
        @include('payment_schedules._sheet_styles')
        /* DejaVu has no Times; keep titles bold serif-like via DejaVu Serif. */
        .ps-title, .ps-date, .ps-sign { font-family: "DejaVu Serif", serif; }
        .ps-table { font-size: 11px; }
        .ps-title { font-size: 15px; }
        .ps-date { font-size: 13px; }
        .ps-sign { font-size: 12px; }
    </style>
</head>
<body>
    @include('payment_schedules._sheet')
</body>
</html>
