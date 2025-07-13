<?php
namespace App\Imports;

use App\Models\Classs;
use App\Models\Lecturer;
use App\Models\LectureAdministered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LectureImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $user = Auth::user();

        $userId = $user->user_id ?? $user->id;

        foreach ($rows as $row) {
            // Skip if required fields are missing
            if (
                empty($row['class_name']) ||
                empty($row['lecturer_name']) ||
                empty($row['lecture_date']) ||
                empty($row['start_time']) ||
                empty($row['end_time'])
            ) {
                continue;
            }

            // Resolve class and lecturer
            $class = Classs::where('name', $row['class_name'])
                ->where('user_id', $userId)
                ->first();

            $lecturer = Lecturer::where('name', $row['lecturer_name'])
                ->where('user_id', $userId)
                ->first();

            if (!$class || !$lecturer) {
                continue;
            }

            // Convert Excel date serial to Y-m-d format
            $lectureDate = is_numeric($row['lecture_date'])
                ? ExcelDate::excelToDateTimeObject($row['lecture_date'])->format('Y-m-d')
                : date('Y-m-d', strtotime($row['lecture_date']));

            // Convert Excel time float to H:i format
            $startTime = is_numeric($row['start_time'])
                ? ExcelDate::excelToDateTimeObject($row['start_time'])->format('H:i')
                : date('H:i', strtotime($row['start_time']));

            $endTime = is_numeric($row['end_time'])
                ? ExcelDate::excelToDateTimeObject($row['end_time'])->format('H:i')
                : date('H:i', strtotime($row['end_time']));

            // Save record
            LectureAdministered::create([
                'user_id' => $userId,
                'classs_id' => $class->id,
                'lecturer_id' => $lecturer->id,
                'lecture_date' => $lectureDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        }
    }
}
