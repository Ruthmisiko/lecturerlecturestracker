<?php

namespace App\Exports;

use App\Support\Theme;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lecturer payment report laid out like the department payment sheet:
 * frozen bold header row, then per department: name, lecturer rows, TOTAL and an AHL summary.
 */
class PaymentReportExport implements FromArray, WithEvents, WithTitle
{
    private const HEADER = ['SNO', 'EMPLOYEE PIN', 'NAME', 'HOURS WORKED', 'RATE', 'GROSS AMOUNT', 'HOUSE LEVY', 'TAX DEDUCTED', 'NET AMOUNT', 'DEPARTMENT', 'PERIOD TAUGHT', 'PV NO VCH NO'];
    private const LAST_COL = 'L';
    private const MONEY_COLS = ['D', 'E', 'F', 'G', 'H', 'I'];

    /** Row numbers by kind, filled while building, used for styling. */
    private array $marks = ['section' => [], 'data' => [], 'total' => [], 'summary' => [], 'grand' => []];

    public function __construct(private Collection $sections, private object $totals, private Theme $theme) {}

    public function title(): string
    {
        return 'Payment Report';
    }

    public function array(): array
    {
        $rows = [self::HEADER];
        $add  = function (array $row, string $kind) use (&$rows) {
            $rows[] = $row;
            if ($kind) {
                $this->marks[$kind][] = count($rows);
            }
        };

        foreach ($this->sections as $section) {
            $add([strtoupper($section->name)], 'section');

            foreach ($section->rows as $i => $r) {
                $add([$i + 1, $r->kra_pin, $r->name, round($r->hours, 2), round($r->rate, 2), round($r->gross, 2),
                    round($r->housing, 2), round($r->tax, 2), round($r->net, 2), $r->department, $r->period_taught, $r->pv_no ?? ''], 'data');
            }

            $t = $section->totals;
            $add(['', '', 'TOTAL', round($t->hours, 2), '', round($t->gross, 2), round($t->housing, 2), round($t->tax, 2), round($t->net, 2)], 'total');
            $add(['', '', 'EMPLOYEE AHL', round($t->housing, 2)], 'summary');
            $add(['', '', 'EMPLOYER AHL', round($t->employer_levy, 2)], 'summary');
            $add(['', '', 'TOTAL AHL', round($t->housing + $t->employer_levy, 2)], 'summary');
            $add(['', '', 'TAX DEDUCTED', round($t->tax, 2)], 'summary');
            $add([' '], ''); // spacer (an empty array would be dropped and shift the styling)
        }

        if ($this->sections->count() > 1) {
            $g = $this->totals;
            $add(['', '', 'GRAND TOTAL', round($g->hours, 2), '', round($g->gross, 2), round($g->housing, 2), round($g->tax, 2), round($g->net, 2)], 'grand');
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->style($e->sheet->getDelegate())];
    }

    private function style(Worksheet $sheet): void
    {
        $primary = ltrim($this->theme->primary, '#');
        $onPrimary = ltrim($this->theme->primaryText(), '#');
        $soft = ltrim($this->theme->primarySoft(), '#');
        $last = $sheet->getHighestRow();
        $fill = fn ($rgb) => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $thin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]]];

        // Header: bold, coloured, frozen while scrolling.
        $sheet->getStyle('A1:' . self::LAST_COL . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $onPrimary]],
            'fill' => $fill($primary),
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ] + $thin);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->freezePane('A2');

        foreach ($this->marks['section'] as $r) {
            $sheet->mergeCells("A{$r}:" . self::LAST_COL . $r);
            $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $primary]]]);
            $sheet->getRowDimension($r)->setRowHeight(22);
        }

        foreach ($this->marks['data'] as $r) {
            $sheet->getStyle("A{$r}:" . self::LAST_COL . $r)->applyFromArray($thin);
            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:" . self::LAST_COL . $r)->applyFromArray(['fill' => $fill('F6F6F6')]);
            }
        }

        foreach (array_merge($this->marks['total'], $this->marks['grand']) as $r) {
            $sheet->getStyle("A{$r}:" . self::LAST_COL . $r)->applyFromArray(['font' => ['bold' => true], 'fill' => $fill($soft)] + $thin);
        }

        // AHL summary: grey bold label + value box, like the PDF.
        foreach ($this->marks['summary'] as $r) {
            $sheet->getStyle("C{$r}")->applyFromArray(['font' => ['bold' => true], 'fill' => $fill('E2E3E5')] + $thin);
            $sheet->getStyle("D{$r}")->applyFromArray(['font' => ['bold' => true], 'fill' => $fill('F1F2F3')] + $thin);
        }

        foreach (self::MONEY_COLS as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$col}1:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getStyle("B2:B{$last}")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("L2:L{$last}")->getNumberFormat()->setFormatCode('@');

        foreach (['A' => 6, 'B' => 16, 'C' => 32, 'D' => 14, 'E' => 11, 'F' => 15, 'G' => 13, 'H' => 15, 'I' => 15, 'J' => 14, 'K' => 16, 'L' => 15] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }
}
