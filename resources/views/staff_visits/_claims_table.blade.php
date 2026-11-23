{{-- Claims sheet table, shared by the on-screen report and the PDF. Expects $rows, $showOther, $total, $institution, $heading, $subheading, $headBg. --}}
<div style="text-align:center; margin-bottom:6px;">
    <div style="font-size:16px; font-weight:bold;">{{ $institution }}</div>
    <div style="font-weight:bold;">{{ $heading }}</div>
    @if ($subheading)<div style="font-weight:bold;">{{ $subheading }}</div>@endif
</div>

<table class="claims">
    <thead>
        <tr>
            <th>NO.</th>
            <th>DATE OF ENTRY</th>
            <th>NAME OF STAFF</th>
            <th>PERSONAL NO.</th>
            <th>JOB GROUP</th>
            <th>VOUCHER NO.</th>
            <th>ACTIVITY DATES</th>
            <th>PURPOSE OF ACTIVITY</th>
            <th>NARRATION</th>
            <th>ITINERARY</th>
            <th>WHO SPONSORED THE ACTIVITY</th>
            <th>NO. OF DAYS</th>
            <th>RATE</th>
            <th>FARE</th>
            <th>MEALS</th>
            @if ($showOther)<th>OTHER</th>@endif
            <th>TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $r)
            <tr>
                <td class="c">{{ $r->no }}</td>
                <td class="nw">{{ $r->entry_date }}</td>
                <td>{{ $r->name }}</td>
                <td>{{ $r->personal_no }}</td>
                <td class="nw">{{ $r->job_group }}</td>
                <td class="nw">{{ $r->voucher_no }}</td>
                <td class="nw">{{ $r->dates }}</td>
                <td>{{ $r->purpose }}</td>
                <td>{{ $r->narration }}</td>
                <td>{{ $r->itinerary }}</td>
                <td>{{ $r->sponsor }}</td>
                <td class="c">{{ $r->days }}</td>
                <td class="r">{{ number_format($r->rate, 2) }}</td>
                <td class="r">{{ $r->fare > 0 ? number_format($r->fare, 2) : '' }}</td>
                <td class="r">{{ $r->meals > 0 ? number_format($r->meals, 2) : '' }}</td>
                @if ($showOther)<td class="r">{{ $r->other > 0 ? number_format($r->other, 2) : '' }}</td>@endif
                <td class="r"><strong>{{ number_format($r->total, 2) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="{{ $showOther ? 17 : 16 }}" class="c" style="padding:20px;">No claims found.</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="{{ $showOther ? 16 : 15 }}" class="r"><strong>TOTAL</strong></td>
                <td class="r"><strong>{{ number_format($total, 2) }}</strong></td>
            </tr>
        </tfoot>
    @endif
</table>
