<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Writes the short voucher narration for a staff visit (e.g. "Payment of per diem to James Kioko for
 * assessing nursing students at Busia, 15–19 Oct 2025") from the purpose, itinerary and dates.
 * Uses Gemini when a key is set up; otherwise (or if Google is busy) builds a plain sentence.
 */
class StaffVisitNarrator
{
    public function narrate(array $v): array
    {
        $fallback = $this->template($v);

        $key = AttendanceSheetReader::apiKey();
        if (!$key) {
            return ['text' => $fallback, 'ai' => false];
        }

        $prompt = "You write the narration line on a payment voucher at a Kenyan medical training college.\n"
            . "Read the nature of the activity below and write ONE formal sentence (max 35 words) saying what the staff member "
            . "is being paid for, e.g. \"Payment of subsistence allowance to <name> for supervising end of semester exams at <place> from <dates>.\" "
            . "Fix spelling mistakes in the purpose, use sentence case, mention the place and dates, and do not invent facts or amounts. "
            . "Return only the sentence.\n\n"
            . "Staff: " . ($v['lecturer'] ?? '') . "\n"
            . "Activity type: " . ($v['type'] ?? '') . "\n"
            . "Purpose: " . ($v['purpose'] ?? '') . "\n"
            . "Itinerary / places: " . ($v['itinerary'] ?? '') . ($v['location'] ?? '' ? ' ' . $v['location'] : '') . "\n"
            . "Dates: " . $this->dates($v) . "\n"
            . "Sponsored by: " . ($v['sponsor'] ?? '');

        $models = collect([config('services.gemini.model')])
            ->merge(explode(',', (string) config('services.gemini.fallback_models')))
            ->map(fn ($m) => trim($m))->filter()->unique()->values();

        foreach ($models as $model) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents'         => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.2],
                    ]);
            } catch (\Throwable $e) {
                continue;
            }

            if ($response->successful()) {
                $text = trim((string) $response->json('candidates.0.content.parts.0.text'), " \t\n\r\"");
                if ($text !== '') {
                    return ['text' => $text, 'ai' => true];
                }
            }

            if (!in_array($response->status(), [404, 500, 502, 503, 504])) {
                break;
            }
        }

        return ['text' => $fallback, 'ai' => false];
    }

    private function template(array $v): string
    {
        $purpose = trim((string) ($v['purpose'] ?? ''));
        $place   = trim((string) ($v['itinerary'] ?? '') ?: (string) ($v['location'] ?? ''));

        return 'Payment of subsistence allowance to ' . ($v['lecturer'] ?? 'staff')
            . ($purpose !== '' ? ' for ' . mb_strtolower($purpose) : '')
            . ($place !== '' ? ' at ' . $place : '')
            . ' (' . $this->dates($v) . ').';
    }

    private function dates(array $v): string
    {
        try {
            $s = Carbon::parse($v['start_date']);
            $e = Carbon::parse($v['end_date'] ?? $v['start_date']);
        } catch (\Throwable $ex) {
            return '';
        }

        return $s->equalTo($e) ? $s->format('d M Y') : $s->format('d M Y') . ' to ' . $e->format('d M Y');
    }
}
