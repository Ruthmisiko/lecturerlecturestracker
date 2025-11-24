<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class LectureExport implements FromCollection
{
    protected $data;
    public function __construct($data) { $this->data = $data; }

    public function collection()
    {
        return $this->data->map(function ($item) {
            return [
                'Lecturer' => $item->lecturer->name,
                'Class' => $item->classs->name,
                'Date' => $item->lecture_date,
                'Start' => $item->start_time,
                'End' => $item->end_time,
                'Status' => $item->status,
            ];
        });
    }
}
