<?php
namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\Classs;
use App\Models\Lecturer;

class LectureTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection()
    {
        return collect([[]]); // Just one empty row for template
    }

    public function headings(): array
    {
        return [
            'Class',
            'Start Time',
            'End Time',
            'Lecturer',
            'Date'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Filtered by user_id
                $classes = Classs::where('user_id', $this->userId)->pluck('name')->toArray();
                $lecturers = Lecturer::where('user_id', $this->userId)->pluck('name')->toArray();

                // Time options (08:00 to 18:00)
                $timeOptions = [];
                for ($i = 8; $i <= 18; $i++) {
                    $timeOptions[] = sprintf("%02d:00", $i);
                }

                // Prepare dropdown strings
                $classList = '"' . implode(',', $classes) . '"';
                $lecturerList = '"' . implode(',', $lecturers) . '"';
                $timeList = '"' . implode(',', $timeOptions) . '"';

                // Apply dropdowns to rows 2 through 100
                for ($row = 2; $row <= 100; $row++) {
                    // Class dropdown
                    $validation = $sheet->getCell("A{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($classList);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);

                    // Start Time dropdown
                    $validation = $sheet->getCell("B{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($timeList);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);

                    // End Time dropdown
                    $validation = $sheet->getCell("C{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($timeList);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);

                    // Lecturer dropdown
                    $validation = $sheet->getCell("D{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($lecturerList);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                }

                // Auto-size columns
                foreach (range('A', 'E') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }
}
