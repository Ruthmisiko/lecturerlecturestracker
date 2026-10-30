<?php

namespace App\Exports\Bulk;

use App\Support\Theme;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** The sheet users fill in: headings, one greyed example row, dropdowns and text-formatted columns. */
class TemplateSheet implements FromArray, WithTitle, WithEvents
{
    private const LAST_ROW = 1000;

    /**
     * @param string[] $headings   column headings (machine names; also what the import reads)
     * @param array    $example    one example row
     * @param array    $dropdowns  column heading => list of allowed values
     * @param string[] $textColumns headings whose cells are formatted as text (keeps leading zeros)
     */
    public function __construct(
        private array $headings,
        private array $example,
        private array $dropdowns = [],
        private array $textColumns = [],
    ) {}

    public function title(): string
    {
        return 'Template';
    }

    public function array(): array
    {
        return [$this->headings, $this->example];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $e) => $this->style($e->sheet->getDelegate())];
    }

    private function column(string $heading): string
    {
        $i = array_search($heading, $this->headings, true);

        return Coordinate::stringFromColumnIndex(($i === false ? 0 : $i) + 1);
    }

    private function style(Worksheet $sheet): void
    {
        $theme = Theme::current();
        $last  = Coordinate::stringFromColumnIndex(count($this->headings));

        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => ltrim($theme->primaryText(), '#')]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ltrim($theme->primary, '#')]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->freezePane('A2');

        // Example row: grey and italic; the import ignores rows starting with "EXAMPLE".
        $sheet->getStyle("A2:{$last}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
        ]);

        foreach ($this->headings as $i => $heading) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($col)->setWidth(max(14, min(34, strlen($heading) + 8)));

            if (in_array($heading, $this->textColumns, true)) {
                $sheet->getStyle("{$col}2:{$col}" . self::LAST_ROW)->getNumberFormat()->setFormatCode('@');
            }
        }

        // Dropdown lists live in hidden columns far to the right.
        $listCol = count($this->headings) + 3;
        foreach ($this->dropdowns as $heading => $values) {
            $values = array_values(array_filter($values, fn ($v) => $v !== null && $v !== ''));
            if (!$values) {
                $listCol++;
                continue;
            }

            $letter = Coordinate::stringFromColumnIndex($listCol++);
            foreach ($values as $i => $value) {
                $sheet->setCellValue($letter . ($i + 1), $value);
            }
            $sheet->getColumnDimension($letter)->setVisible(false);

            $target     = $this->column($heading);
            $validation = new DataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(false); // false = show the in-cell arrow
            $validation->setShowErrorMessage(false);
            $validation->setFormula1(sprintf('$%s$1:$%s$%d', $letter, $letter, count($values)));
            $sheet->setDataValidation("{$target}3:{$target}" . self::LAST_ROW, $validation);
        }
    }
}
