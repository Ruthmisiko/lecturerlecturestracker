<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { margin-bottom: 4px; }
        .meta { font-size: 10px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e7e34; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f7f7f7; }
        .ok         { color: #1e7e34; font-weight: bold; }
        .own_clash  { color: #856404; font-weight: bold; }
        .clash_with { color: #721c24; font-weight: bold; }
        .both       { color: #721c24; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Lectures Administered — {{ $lecturer->name }}</h2>
    <div class="meta">
        Department: {{ $lecturer->department->name ?? '-' }} &nbsp;|&nbsp;
        ID Number: {{ $lecturer->id_number ?? '-' }} &nbsp;|&nbsp;
        Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Class</th>
                <th>Unit</th>
                <th>Lecture Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lectures as $i => $record)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $record->classs->name ?? '-' }}</td>
                <td>{{ $record->unit->name ?? '-' }}</td>
                <td>{{ $record->lecture_date }}</td>
                <td>{{ $record->start_time }}</td>
                <td>{{ $record->end_time }}</td>
                <td class="{{ $record->computed_status }}">
                    @if($record->computed_status === 'ok')
                        OK
                    @elseif($record->computed_status === 'own_clash')
                        Own Clash
                    @elseif($record->computed_status === 'clash_with')
                        Clash with: {{ $record->clash_with_names }}
                    @else
                        Own Clash &amp; Clash with: {{ $record->clash_with_names }}
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#888;">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
