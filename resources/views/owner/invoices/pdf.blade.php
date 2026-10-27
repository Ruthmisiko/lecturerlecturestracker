<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: 16mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; }
        @include('owner.invoices._styles')
        .inv { font-size: 11px; }
    </style>
</head>
<body>
    @include('owner.invoices._document', ['forPdf' => true])
</body>
</html>
