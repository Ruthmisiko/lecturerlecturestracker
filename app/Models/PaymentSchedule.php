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

    public function total(): float
    {
        return (float) $this->items->sum('amount');
    }
}
