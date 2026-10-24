{{-- The schedule itself, shared by the web view and the PDF. --}}
<div class="ps-sheet">
    <div class="ps-title">{{ $schedule->title }}</div>
    <div class="ps-date">{{ $schedule->schedule_date->format('d.m.Y') }}</div>

    <table class="ps-table">
        <thead>
            <tr class="ps-group">
                <th colspan="2"></th>
                <th colspan="5" class="ps-left">BANK ACCOUNT DETAILS</th>
            </tr>
            <tr>
                <th class="ps-sno">S/NO</th>
                <th class="ps-left">NAME</th>
                <th class="ps-left">ACCOUNT NO.</th>
                <th class="ps-right">AMOUNT</th>
                <th class="ps-left">BANK</th>
                <th class="ps-left">BRANCH</th>
                <th class="ps-right">ID NO.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedule->items as $i => $item)
                <tr>
                    <td class="ps-sno">{{ $i + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->account_number }}</td>
                    <td class="ps-right">{{ number_format($item->amount, 2) }}</td>
                    <td>{{ $item->bank }}</td>
                    <td>{{ $item->branch }}</td>
                    <td class="ps-right">{{ $item->id_number }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>TOTAL</td>
                <td></td>
                <td class="ps-right">{{ number_format($schedule->total(), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <table class="ps-sign">
        <tr>
            <td>
                <div class="ps-sign-label">Authorized By</div>
                <div class="ps-sign-line"></div>
                <div class="ps-sign-name">{{ $schedule->authorized_by_name }}</div>
                <div class="ps-sign-name">{{ $schedule->authorized_by_position }}</div>
            </td>
            <td>
                <div class="ps-sign-label">APPROVED BY</div>
                <div class="ps-sign-line"></div>
                <div class="ps-sign-name">{{ $schedule->approved_by_name }}</div>
                <div class="ps-sign-name">{{ $schedule->approved_by_position }}</div>
            </td>
        </tr>
    </table>
</div>
