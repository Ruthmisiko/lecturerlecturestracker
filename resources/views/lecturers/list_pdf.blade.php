<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    @php $theme = \App\Support\Theme::current(); @endphp
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { margin-bottom: 4px; }
        .meta { font-size: 10px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: {{ $theme->primary }}; color: {{ $theme->primaryText() }}; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f7f7f7; }
    </style>
</head>
<body>
    <h2>{{ $theme->institution->name ?? 'Lecturers' }} — Lecturers</h2>
    <div class="meta">
        Total: {{ $lecturers->count() }} &nbsp;|&nbsp;
        Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Department</th>
                <th>Email</th>
                <th>Phone</th>
                <th>ID Number</th>
                <th>KRA PIN</th>
                <th>Specialization</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lecturers as $i => $lecturer)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $lecturer->name }}</td>
                <td>{{ $lecturer->department->name ?? '-' }}</td>
                <td>{{ $lecturer->email }}</td>
                <td>{{ $lecturer->phone }}</td>
                <td>{{ $lecturer->id_number }}</td>
                <td>{{ $lecturer->kra_pin }}</td>
                <td>{{ $lecturer->specialization }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:#888;">No lecturers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
