<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

/** Voucher number and transaction reference saved for a lecturer's payment in a report period. */
class PaymentReference extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['lecturer_id', 'department_key', 'period_key', 'pv_no', 'transaction_ref', 'user_id'];

    public static function keyFor(?string $from, ?string $to): string
    {
        return ($from ?: '') . '|' . ($to ?: '');
    }
}
