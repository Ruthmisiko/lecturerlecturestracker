<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }

    .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #333; padding-bottom: 8px; }
    .header h2 { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
    .header p  { font-size: 10px; color: #555; }

    .meta { margin-bottom: 10px; font-size: 9.5px; }
    .meta span { margin-right: 18px; }
    .meta strong { color: #333; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th { background: #2d6a4f; color: #fff; padding: 5px 6px; text-align: left; font-size: 9.5px; }
    td { padding: 4px 6px; border-bottom: 1px solid #ddd; font-size: 9px; vertical-align: top; }
    tr:nth-child(even) td { background: #f5f5f5; }

    .badge-clash  { background: #c0392b; color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 8px; }
    .badge-ok     { background: #27ae60; color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 8px; }
    .badge-own    { background: #e74c3c; color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 8px; }

    .vs { font-weight: bold; color: #c0392b; text-align: center; font-size: 9px; padding: 2px; }
    .clash-block { border: 1px solid #e74c3c; border-radius: 4px; margin-bottom: 6px; }
    .clash-block .row-a { background: #fdf0ef; }
    .clash-block .row-b { background: #fff8f0; }
    .clash-block .row-vs td { background: #fce4e4; font-weight: bold; color: #c0392b; text-align: center; font-size: 8px; }

    .signatures { margin-top: 40px; width: 100%; }
    .sig-table { width: 100%; border-collapse: collapse; }
    .sig-cell { width: 50%; padding: 0 20px; vertical-align: bottom; }
    .sig-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 4px; font-size: 9px; color: #333; }
    .sig-title { font-weight: bold; font-size: 10px; margin-bottom: 4px; }
    .sig-name  { font-size: 9px; color: #555; }

    .page-footer { text-align: center; font-size: 8px; color: #aaa; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <h2>Lecturer Tracker &mdash; Lecture Administered Report</h2>
    <p>Generated: {{ now()->format('d M Y, H:i') }}</p>
</div>

{{-- FILTER META --}}
<div class="meta">
    @if($department)
        <span><strong>Department:</strong> {{ $department->name }}</span>
    @endif
    @if(!empty($filters['lecturer']))
        <span><strong>Lecturer:</strong> {{ $filters['lecturer'] }}</span>
    @endif
    @if(!empty($filters['class']))
        <span><strong>Class:</strong> {{ $filters['class'] }}</span>
    @endif
    @if(!empty($filters['lecture_date']))
        <span><strong>Date:</strong> {{ $filters['lecture_date'] }}</span>
    @endif
    @if(!empty($filters['status']))
        @php $statusLabel = ['own_clash'=>'Own Clash','clash_with'=>'Clash With Other Lecturer','ok'=>'OK'][$filters['status']] ?? $filters['status']; @endphp
        <span><strong>Status:</strong> {{ $statusLabel }}</span>
    @endif
    <span><strong>Total Records:</strong> {{ $records->count() }}</span>
</div>

@php
    $isOwnClashReport  = ($status === 'own_clash');
    $isClashWithReport = ($status === 'clash_with');
@endphp

{{-- CLASH PAIR REPORT --}}
@if($isOwnClashReport || $isClashWithReport)

    @php
        $pool  = $isOwnClashReport ? $ownClashPool : $clashes;
        $pairs = collect();
        $seen  = collect();

        foreach ($records as $item) {
            if ($seen->contains($item->id)) continue;

            if ($isOwnClashReport) {
                $match = $pool->first(fn($c) =>
                    $c->id != $item->id &&
                    $c->lecturer_id == $item->lecturer_id &&
                    $c->lecture_date == $item->lecture_date &&
                    !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
                );
            } else {
                $match = $pool->first(fn($c) =>
                    $c->id != $item->id &&
                    $c->classs_id == $item->classs_id &&
                    $c->lecture_date == $item->lecture_date &&
                    $c->lecturer_id != $item->lecturer_id &&
                    !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
                );
            }

            if ($match) {
                $pairs->push([$item, $match]);
                $seen->push($item->id);
                $seen->push($match->id);
            }
        }
    @endphp

    @if($pairs->isEmpty())
        <p style="color:#888; font-style:italic;">No clash pairs found for the selected filters.</p>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:18%">Lecturer</th>
                <th style="width:14%">Class</th>
                <th style="width:18%">Unit</th>
                <th style="width:9%">Date</th>
                <th style="width:7%">Start</th>
                <th style="width:7%">End</th>
                <th style="width:6%">&nbsp;</th>
                <th style="width:18%">{{ $isOwnClashReport ? 'Also Scheduled For' : 'Clashing Lecturer' }}</th>
                <th style="width:14%">Class</th>
                <th style="width:18%">Unit</th>
                <th style="width:9%">Date</th>
                <th style="width:7%">Start</th>
                <th style="width:7%">End</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pairs as [$a, $b])
            <tr>
                <td>{{ $a->lecturer->name ?? '-' }}</td>
                <td>{{ $a->classs->name ?? '-' }}</td>
                <td>{{ $a->unit->name ?? '-' }}</td>
                <td>{{ $a->lecture_date }}</td>
                <td>{{ $a->start_time }}</td>
                <td>{{ $a->end_time }}</td>
                <td class="vs">CLASH</td>
                <td>{{ $b->lecturer->name ?? '-' }}</td>
                <td>{{ $b->classs->name ?? '-' }}</td>
                <td>{{ $b->unit->name ?? '-' }}</td>
                <td>{{ $b->lecture_date }}</td>
                <td>{{ $b->start_time }}</td>
                <td>{{ $b->end_time }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

{{-- STANDARD REPORT --}}
@else
<table>
    <thead>
        <tr>
            <th>Lecturer</th>
            <th>Class</th>
            <th>Unit</th>
            <th>Department</th>
            <th>Date</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $item)
        @php
            $ownC = $ownClashPool->contains(fn($c) =>
                $c->id != $item->id &&
                $c->lecturer_id == $item->lecturer_id &&
                $c->lecture_date == $item->lecture_date &&
                !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
            );
            $withC = $clashes->contains(fn($c) =>
                $c->id != $item->id &&
                $c->classs_id == $item->classs_id &&
                $c->lecture_date == $item->lecture_date &&
                $c->lecturer_id != $item->lecturer_id &&
                !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
            );
            $statusBadge = $ownC ? 'Own Clash' : ($withC ? 'Clash' : 'OK');
            $badgeClass  = $ownC ? 'badge-own' : ($withC ? 'badge-clash' : 'badge-ok');
        @endphp
        <tr>
            <td>{{ $item->lecturer->name ?? '-' }}</td>
            <td>{{ $item->classs->name ?? '-' }}</td>
            <td>{{ $item->unit->name ?? '-' }}</td>
            <td>{{ $item->department->name ?? '-' }}</td>
            <td>{{ $item->lecture_date }}</td>
            <td>{{ $item->start_time }}</td>
            <td>{{ $item->end_time }}</td>
            <td><span class="{{ $badgeClass }}">{{ $statusBadge }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- SIGNATURES --}}
<div class="signatures">
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">Checked by Accountant</div>
                <div class="sig-line">
                    Name: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div class="sig-line" style="margin-top:16px;">
                    Signature: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div class="sig-line" style="margin-top:16px;">
                    Date: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
            </td>
            <td class="sig-cell">
                <div class="sig-title">Approved by Principal</div>
                <div class="sig-line">
                    Name: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div class="sig-line" style="margin-top:16px;">
                    Signature: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div class="sig-line" style="margin-top:16px;">
                    Date: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="page-footer">
    Lecturer Tracker System &bull; Confidential &bull; {{ now()->format('d M Y') }}
</div>

</body>
</html>
