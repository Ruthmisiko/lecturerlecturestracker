<?php

namespace App\Imports;

use App\Models\LectureAdministered;
use App\Models\Classs;
use App\Models\Lecturer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class LectureImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * Transform a row into a model.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Find class by name
        $class = Classs::where('name', $row['class'])->first();
        if (!$class) {
            throw new \Exception("Class '{$row['class']}' not found.");
        }

        // Find lecturer by name
        $lecturer = Lecturer::where('name', $row['lecturer'])->first();
        if (!$lecturer) {
            throw new \Exception("Lecturer '{$row['lecturer']}' not found.");
        }

        // Convert and validate times
        $start_time = $this->validateAndFormatTime($row['start_time'], 'Start Time');
        $end_time = $this->validateAndFormatTime($row['end_time'], 'End Time');

        // Validate end time is after start time
        if (strtotime($end_time) <= strtotime($start_time)) {
            throw new \Exception("End Time must be after Start Time for row with lecturer '{$row['lecturer']}'.");
        }

        // Validate date format
        $lecture_date = $this->validateDate($row['date'], 'Date');

        // Check for duplicates
        $duplicate = LectureAdministered::where([
            'lecturer_id' => $lecturer->id,
            'classs_id' => $class->id,
            'lecture_date' => $lecture_date,
        ])->exists();

        if ($duplicate) {
            throw new \Exception("Duplicate entry found for lecturer '{$row['lecturer']}' in class '{$row['class']}' on date '{$row['date']}'.");
        }

        return new LectureAdministered([
            'lecturer_id' => $lecturer->id,
            'classs_id' => $class->id,
            'lecture_date' => $lecture_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ]);
    }

    /**
     * Define validation rules for each row.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'class' => 'required|exists:classses,name',
            'lecturer' => 'required|exists:lecturers,name',
            'start_time' => 'required',
            'end_time' => 'required',
            'date' => 'required',
        ];
    }

    /**
     * Validate and format time, including Excel serial times.
     *
     * @param mixed $time
     * @param string $field
     * @return string
     * @throws \Exception
     */
    private function validateAndFormatTime($time, $field)
    {
        // Handle Excel serial time (e.g., 0.333333 for 8:00 AM)
        if (is_numeric($time) && $time >= 0 && $time <= 1) {
            try {
                // Convert Excel serial time to HH:MM
                $timeString = Date::excelToDateTimeObject($time)->format('H:i');
            } catch (\Exception $e) {
                throw new \Exception("Invalid {$field} format: {$time}. Could not convert Excel time.");
            }
        } else {
            // Assume time is already in string format (e.g., "08:00")
            $timeString = $time;
        }

        // Validate the time format
        $validator = Validator::make(['time' => $timeString], [
            'time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            throw new \Exception("Invalid {$field} format: {$time}. Must be in HH:MM format (e.g., 08:00).");
        }

        return $timeString;
    }

    /**
     * Validate and format date.
     *
     * @param string $date
     * @param string $field
     * @return string
     * @throws \Exception
     */
    private function validateDate($date, $field)
    {
        // Handle Excel date serial numbers or string dates
        try {
            if (is_numeric($date)) {
                // Convert Excel serial date to Y-m-d format
                $dateTime = Date::excelToDateTimeObject($date);
                $formattedDate = $dateTime->format('Y-m-d');
            } else {
                // Try parsing string date
                $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            throw new \Exception("Invalid {$field} format: {$date}. Must be a valid date (e.g., YYYY-MM-DD or MM/DD/YYYY).");
        }

        $validator = Validator::make(['date' => $formattedDate], [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            throw new \Exception("Invalid {$field} format: {$date}. Must be in YYYY-MM-DD format.");
        }

        return $formattedDate;
    }

    /**
     * Custom validation messages.
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'class.required' => 'The Class field is required.',
            'class.exists' => 'The selected Class does not exist.',
            'lecturer.required' => 'The Lecturer field is required.',
            'lecturer.exists' => 'The selected Lecturer does not exist.',
            'start_time.required' => 'The Start Time field is required.',
            'end_time.required' => 'The End Time field is required.',
            'date.required' => 'The Date field is required.',
        ];
    }
}