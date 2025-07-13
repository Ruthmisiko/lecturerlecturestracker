<?php

namespace App\Exports;

use App\Models\Classs;
use App\Models\Lecturer;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class LectureTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    protected $classes;
    protected $lecturers;

    public function __construct($userId)
    {
        $ownerId = \App\Models\User::find($userId)?->user_id ?? $userId;

        $this->classes = Classs::where('user_id', $ownerId)->pluck('name')->toArray();
        $this->lecturers = Lecturer::where('user_id', $ownerId)->pluck('name')->toArray();
    }

    public function collection()
    {
        // Only one row of sample data
        return collect([[
            '', '', '', '', ''
        ]]);
    }

    public function headings(): array
    {
        return [
            'class_name',
            'lecturer_name',
            'lecture_date',
            'start_time',
            'end_time',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Store class names in column AA
                foreach ($this->classes as $i => $class) {
                    $sheet->setCellValue('AA' . ($i + 1), $class);
                }

                // Store lecturer names in column AB
                foreach ($this->lecturers as $i => $lecturer) {
                    $sheet->setCellValue('AB' . ($i + 1), $lecturer);
                }

                // Store time options (07:00 to 19:00 hourly) in column AC
                $start = strtotime("07:00");
                $end = strtotime("19:00");
                $i = 1;
                while ($start <= $end) {
                    $sheet->setCellValue('AC' . $i, date("H:i", $start));
                    $start += 60 * 60;
                    $i++;
                }

                // Apply dropdowns to rows 2 through 1000
                for ($row = 2; $row <= 1000; $row++) {
                    $dropdowns = [
                        "A{$row}" => '$AA$1:$AA$100', // class_name
                        "B{$row}" => '$AB$1:$AB$100', // lecturer_name
                        "D{$row}" => '$AC$1:$AC$20',  // start_time
                        "E{$row}" => '$AC$1:$AC$20',  // end_time
                    ];

                    foreach ($dropdowns as $cell => $range) {
                        $validation = $sheet->getCell($cell)->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(false);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('='.$range);
                        $validation->setShowErrorMessage(true);
                        $validation->setErrorTitle('Invalid Input');
                        $validation->setError('Please select a valid option from the list.');
                    }
                }

                // Optionally hide the data source columns
                $sheet->getColumnDimension('AA')->setVisible(false);
                $sheet->getColumnDimension('AB')->setVisible(false);
                $sheet->getColumnDimension('AC')->setVisible(false);

                // Freeze the header row
                $sheet->freezePane('A2');
            },
        ];
    }
}
