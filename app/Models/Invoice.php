<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];

    protected $fillable = [
        'number', 'institution_id', 'bill_to_name', 'bill_to_email', 'issue_date', 'due_date',
        'items', 'total', 'currency', 'status', 'paid_at', 'notes',
    ];

    protected $casts = [
        'items'      => 'array',
        'total'      => 'float',
        'issue_date' => 'date:Y-m-d',
        'due_date'   => 'date:Y-m-d',
        'paid_at'    => 'date:Y-m-d',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /** Next number in the INV-0001 sequence. */
    public static function nextNumber(): string
    {
        $last = static::where('number', 'like', 'INV-%')->orderByDesc('id')->value('number');
        $n    = $last ? ((int) substr($last, 4)) + 1 : 1;

        do {
            $number = 'INV-' . str_pad($n++, 4, '0', STR_PAD_LEFT);
        } while (static::where('number', $number)->exists());

        return $number;
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['draft', 'sent']) && $this->due_date && $this->due_date->isPast() && !$this->due_date->isToday();
    }

    /** Status shown to people: "Overdue" when an unpaid invoice is past its due date. */
    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'Overdue' : self::STATUSES[$this->status] ?? $this->status;
    }
}
