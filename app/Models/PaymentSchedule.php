<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'title', 'schedule_date', 'period_from', 'period_to', 'statuses',
        'authorized_by_name', 'authorized_by_position', 'approved_by_name', 'approved_by_position',
        'user_id',
    ];

    protected $casts = [
        'schedule_date' => 'date:Y-m-d',
        'period_from'   => 'date:Y-m-d',
        'period_to'     => 'date:Y-m-d',
        'statuses'      => 'array',
    ];

    public function items()
    {
        return $this->hasMany(PaymentScheduleItem::class)->orderBy('position');
    }

    /**
     * lecturer_id => voucher number(s) entered on the Payment Report for this schedule's period.
     * A lecturer paid under several departments has one per department, joined with commas.
     */
    public function pvNumbers(): array
    {
        $key = PaymentReference::keyFor($this->period_from?->format('Y-m-d'), $this->period_to?->format('Y-m-d'));

        return PaymentReference::where('period_key', $key)
            ->whereIn('lecturer_id', $this->items->pluck('lecturer_id')->filter())
            ->whereNotNull('pv_no')->where('pv_no', '!=', '')
            ->orderBy('department_key')->orderBy('id')
            ->get()
            ->groupBy('lecturer_id')
            ->map(fn ($refs) => $refs->pluck('pv_no')->unique()->implode(', '))
            ->all();
    }

    public function total(): float
    {
        return (float) $this->items->sum('amount');
    }
}
