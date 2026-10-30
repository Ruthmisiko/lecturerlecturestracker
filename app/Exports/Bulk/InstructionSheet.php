<?php

namespace App\Exports\Bulk;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Plain-language instructions that travel with the template. */
class InstructionSheet implements FromArray, WithTitle, WithEvents
{
    /** @param array<int, array{0:string,1:string,2:string}> $columns [column, required?, explanation] */
    public function __construct(private string $heading, private array $columns, private array $tips) {}

    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        $rows = [[$this->heading], [], ['Column', 'Required?', 'What to enter']];
        foreach ($this->columns as $c) {
            $rows[] = $c;
        }
        $rows[] = [];
        $rows[] = ['Tips'];
        foreach ($this->tips as $tip) {
            $rows[] = ['•', '', $tip];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $sheet = $e->sheet->getDelegate();
            $sheet->getColumnDimension('A')->setWidth(22);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(95);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3:C3')->getFont()->setBold(true);
            $sheet->getStyle('C1:C200')->getAlignment()->setWrapText(true);
            $sheet->getStyle('A4:A200')->getAlignment()->setVertical('top');
        }];
    }
}
