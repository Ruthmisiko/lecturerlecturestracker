<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use BelongsToInstitution;

    /** Placeholders filled in from the lecturer's payslip when the template is picked, by group. */
    public const PLACEHOLDERS = [
        'Lecturer' => [
            '{name}'       => 'Lecturer name',
            '{email}'      => 'Email',
            '{phone}'      => 'Phone',
            '{id_number}'  => 'ID number',
            '{kra_pin}'    => 'KRA PIN',
            '{department}' => 'Department',
        ],
        'Bank' => [
            '{account_number}' => 'Account number',
            '{bank}'           => 'Bank',
            '{branch}'         => 'Branch',
        ],
        'Lectures' => [
            '{period}'        => 'Payslip period',
            '{period_taught}' => 'Months taught',
            '{sessions}'      => 'Lecture sessions',
            '{hours}'         => 'Payable hours',
            '{units}'         => 'List of units with hours',
            '{last_lecture}'  => 'Last lecture date',
        ],
        'Pay' => [
            '{rate}'              => 'Rate per hour',
            '{gross}'             => 'Gross amount',
            '{tax_rate}'          => 'PAYE %',
            '{tax}'               => 'PAYE amount',
            '{housing_levy_rate}' => 'Housing levy %',
            '{housing_levy}'      => 'Housing levy amount',
            '{net}'               => 'Net pay',
        ],
        'Other' => [
            '{institution}' => 'Institution name',
            '{date}'        => "Today's date",
        ],
    ];

    protected $fillable = ['name', 'subject', 'body', 'is_default', 'user_id'];

    protected $casts = ['is_default' => 'boolean'];

    /** Replaces the placeholders in $text with one lecturer's details (same values as the Email Payslip window). */
    public static function fillPlaceholders(string $text, object $row, string $period, string $institution): string
    {
        $missing = '(not on record)';
        $num     = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
        $ksh     = fn ($v) => 'KSh ' . number_format((float) $v, 2);
        $date    = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
        $units   = collect($row->unit_details ?? [])
            ->map(fn ($u) => '- ' . ($u['code'] ? $u['code'] . ' ' : '') . $u['name'] . ': ' . $num($u['counted']) . ' hrs')
            ->join("\n");

        $values = [
            'name'              => $row->name,
            'email'             => $row->email ?: $missing,
            'phone'             => $row->phone ?: $missing,
            'id_number'         => $row->id_number ?: $missing,
            'kra_pin'           => $row->kra_pin && $row->kra_pin !== '—' ? $row->kra_pin : $missing,
            'department'        => $row->department ?? '',
            'account_number'    => $row->account_number ?: $missing,
            'bank'              => $row->bank_name ?: $missing,
            'branch'            => $row->bank_branch ?: $missing,
            'period'            => $period,
            'period_taught'     => $row->period_taught ?: '—',
            'sessions'          => $row->sessions,
            'hours'             => $num($row->hours),
            'units'             => $units ?: 'No units',
            'last_lecture'      => $date($row->last_lecture),
            'rate'              => $ksh($row->rate),
            'gross'             => $ksh($row->gross),
            'tax_rate'          => $num($row->tax_rate) . '%',
            'tax'               => $ksh($row->tax),
            'housing_levy_rate' => $num($row->housing_levy_rate) . '%',
            'housing_levy'      => $ksh($row->housing),
            'net'               => $ksh($row->net),
            'institution'       => $institution,
            'date'              => now()->format('d M Y'),
        ];

        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => array_key_exists($m[1], $values) ? (string) $values[$m[1]] : $m[0], $text);
    }

    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
