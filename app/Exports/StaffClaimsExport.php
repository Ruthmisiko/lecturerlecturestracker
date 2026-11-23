<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** Staff claims sheet: institution title, "claims as at" heading, blue header row, one row per visit, total. */
class StaffClaimsExport implements FromArray, WithEvents, WithTitle
{
    private bool $showOther;
    private array $header;
    private int $columns;

    public function __construct(private Collection $rows, private string $institution, private string $heading, private string $subheading = '')
    {
        $this->showOther = $rows->sum('other') > 0;
        $this->header    = array_values(array_filter([
            'NO.', 'DATE OF ENTRY', 'NAME OF STAFF', 'PERSONAL NO.', 'JOB GROUP', 'VOUCHER NO.', 'ACTIVITY DATES',
            'PURPOSE OF ACTIVITY', 'NARRATION', 'ITINERARY', 'WHO SPONSORED THE ACTIVITY', 'NO. OF DAYS',
            'RATE', 'FARE', 'MEALS', $this->showOther ? 'OTHER' : null, 'TOTAL',
        ]));
        $this->columns = count($this->header);
    }

    public function title(): string
    {
        return 'Staff Claims';
    }

    public function array(): array
    {
        $out = [[$this->institution], [$this->heading], [$this->subheading], $this->header];

        foreach ($this->rows as $r) {
            $line = [
                $r->no, $r->entry_date, $r->name, $r->personal_no, $r->job_group, $r->voucher_no ?? '', $r->dates,
                $r->purpose, $r->narration ?? '', $r->itinerary, $r->sponsor, $r->days,
                round($r->rate, 2), round($r->fare, 2), round($r->meals, 2),
            ];
            if ($this->showOther) {
                $line[] = round($r->other, 2);
            }
            $line[] = round($r->total, 2);
            $out[]  = $line;
        }

        $total = array_fill(0, $this->columns, '');
        $total[$this->columns - 2] = 'TOTAL';
        $total[$this->columns - 1] = round($this->rows->sum('total'), 2);
        $out[] = $total;

        return $out;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $last  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->columns);
            $end   = 4 + $this->rows->count() + 1;
            $moneyFrom = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->columns - ($this->showOther ? 4 : 3));

            foreach ([1, 2, 3] as $row) {
                $sheet->mergeCells("A{$row}:{$last}{$row}");
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            }
            $sheet->getStyle('A1')->getFont()->setSize(14);

            $sheet->getStyle("A4:{$last}4")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle("A4:{$last}{$end}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A5:{$last}{$end}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getStyle("{$moneyFrom}5:{$last}{$end}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$end}:{$last}{$end}")->getFont()->setBold(true);

            // Column widths: narrow for numbers, wide for the text columns.
            $widths = [5, 11, 26, 13, 10, 12, 22, 40, 45, 26, 16, 8, 12, 12, 12];
            if ($this->showOther) {
                $widths[] = 12;
            }
            $widths[] = 14;
            foreach ($widths as $i => $w) {
                $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
            }

            $sheet->freezePane('A5');
        }];
    }
}
