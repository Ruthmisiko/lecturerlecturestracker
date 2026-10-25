<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class PayslipEmail extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['lecturer_id', 'email_configuration_id', 'to', 'subject', 'period_from', 'period_to', 'user_id'];
}
