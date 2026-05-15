<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LectureExport implements FromCollection, WithHeadings, WithStyles
{
    protected \Illuminate\Support\Collection $data;
    protected \Illuminate\Support\Collection $ownClashPool;
    protected \Illuminate\Support\Collection $clashes;

    public function __construct(\Illuminate\Support\Collection $data, ?\Illuminate\Support\Collection $ownClashPool = null, ?\Illuminate\Support\Collection $clashes = null)
    {
        $this->data         = $data;
        $this->ownClashPool = $ownClashPool ?? collect();
        $this->clashes      = $clashes ?? collect();
    }

    public function headings(): array
    {
        return ['Lecturer', 'Class', 'Unit', 'Department', 'Date', 'Start Time', 'End Time', 'Status'];
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            $ownC = $this->ownClashPool->contains(fn($c) =>
                $c->id != $item->id &&
                $c->lecturer_id == $item->lecturer_id &&
                $c->lecture_date == $item->lecture_date &&
                !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
            );
            $withC = $this->clashes->contains(fn($c) =>
                $c->id != $item->id &&
                $c->classs_id == $item->classs_id &&
                $c->lecture_date == $item->lecture_date &&
                $c->lecturer_id != $item->lecturer_id &&
                !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
            );

            return [
                $item->lecturer->name   ?? '-',
                $item->classs->name     ?? '-',
                $item->unit->name       ?? '-',
                $item->department->name ?? '-',
                $item->lecture_date,
                $item->start_time,
                $item->end_time,
                $ownC ? 'Own Clash' : ($withC ? 'Clash With Other Lecturer' : 'OK'),
            ];
        });
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2d6a4f']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
