<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads a photographed / scanned lecturer attendance sheet with Google Gemini and returns
 * one row per lecture with its date and start/end time. Everything else on the sheet is ignored.
 */
class AttendanceSheetReader
{
    private const PROMPT = <<<'TXT'
You are reading a photographed or scanned "External Lecturers' Records" attendance sheet from a Kenyan college.
The table has columns like: Lecture Date | Time | Total Hours | Topic Covered | Class rep Signature | Lecturer's Name | Lecturer's Signature.

Extract ONLY the lecture rows that have a date written. For each row return:
- date: the lecture date as YYYY-MM-DD. Dates are written DAY first (e.g. 9/03/26 = 9 March 2026). A 2-digit year means 20YY.
  If one row's year disagrees with the rest of the sheet (e.g. 2025 among 2026s), use the sheet's usual year and set confidence to "low".
- start_time and end_time: 24-hour HH:MM. Times like "8:00-10:00" are morning; "2:00-4:00" or "2.00-4.00" are afternoon (14:00-16:00).
- raw: the date and time exactly as handwritten, e.g. "9/03/26 8:00-10:00".
- confidence: "high" if clearly readable, otherwise "low".

Ignore the header, topics, signatures, totals, stamps and empty rows. Do not invent rows.
TXT;

    private const SCHEMA = [
        'type'       => 'OBJECT',
        'properties' => [
            'rows' => [
                'type'  => 'ARRAY',
                'items' => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'date'       => ['type' => 'STRING'],
                        'start_time' => ['type' => 'STRING'],
                        'end_time'   => ['type' => 'STRING'],
                        'raw'        => ['type' => 'STRING'],
                        'confidence' => ['type' => 'STRING', 'enum' => ['high', 'low']],
                    ],
                    'required' => ['date', 'start_time', 'end_time', 'confidence'],
                ],
            ],
        ],
        'required' => ['rows'],
    ];

    public static function configured(): bool
    {
        return (bool) config('services.gemini.key');
    }

    /**
     * @return array<int, array{date: ?string, start_time: ?string, end_time: ?string, raw: string, low: bool, problem: ?string}>
     */
    public function read(UploadedFile $file): array
    {
        $key = config('services.gemini.key');

        if (!$key) {
            throw new RuntimeException('Attendance reading is not set up yet (GEMINI_API_KEY is missing on the server).');
        }

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => self::PROMPT],
                    ['inline_data' => [
                        'mime_type' => $file->getMimeType(),
                        'data'      => base64_encode(file_get_contents($file->getRealPath())),
                    ]],
                ],
            ]],
            'generationConfig' => [
                'temperature'      => 0,
                'responseMimeType' => 'application/json',
                'responseSchema'   => self::SCHEMA,
            ],
        ];

        // Free-tier models are often briefly busy or slow: try the main model, then the fallbacks.
        $models = collect([config('services.gemini.model')])
            ->merge(explode(',', (string) config('services.gemini.fallback_models')))
            ->map(fn ($m) => trim($m))->filter()->unique()->values();

        $response = null;
        foreach ($models as $model) {
            try {
                $response = Http::timeout(40)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $response = null; // timed out: try the next model
                continue;
            }

            // Busy (5xx) or model not offered to this key (404): try the next model.
            if (in_array($response->status(), [404, 500, 502, 503, 504])) {
                continue;
            }
            break;
        }

        if (!$response || in_array($response->status(), [404, 500, 502, 503, 504])) {
            throw new RuntimeException('Google\'s free Gemini service is busy right now. Please try again in a few minutes.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('The free Gemini limit was reached. Wait a minute (or until tomorrow for the daily limit) and try again.');
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?: ('HTTP ' . $response->status());
            throw new RuntimeException('Gemini could not read the file: ' . $message);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $data = $text ? json_decode($text, true) : null;

        if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
            throw new RuntimeException('Gemini returned an unexpected answer for this file. Try a clearer photo.');
        }

        return array_map(fn ($row) => $this->normalise((array) $row), $data['rows']);
    }

    /** Validate and tidy one extracted row; flag anything that needs a human look. */
    private function normalise(array $row): array
    {
        $problem = null;

        $date = null;
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', (string) ($row['date'] ?? ''));
            if ($parsed && $parsed->format('Y-m-d') === ($row['date'] ?? '')) {
                $date = $parsed->format('Y-m-d');
            }
        } catch (\Throwable $e) {
        }
        if (!$date) {
            $problem = 'Date not readable';
        }

        $time = function ($value) {
            $value = str_replace('.', ':', trim((string) $value));
            return preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $m) ? sprintf('%02d:%02d', $m[1], $m[2]) : null;
        };
        $start = $time($row['start_time'] ?? null);
        $end   = $time($row['end_time'] ?? null);

        if (!$start || !$end) {
            $problem ??= 'Time not readable';
        } elseif ($end <= $start) {
            $problem ??= 'End time is not after start time';
        }

        return [
            'date'       => $date,
            'start_time' => $start,
            'end_time'   => $end,
            'raw'        => trim((string) ($row['raw'] ?? '')),
            'low'        => ($row['confidence'] ?? 'low') !== 'high',
            'problem'    => $problem,
        ];
    }
}
