<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentScheduleItem extends Model
{
    protected $fillable = [
        'payment_schedule_id', 'lecturer_id', 'position', 'name',
        'account_number', 'amount', 'bank', 'branch', 'id_number',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function schedule()
    {
        return $this->belongsTo(PaymentSchedule::class, 'payment_schedule_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
