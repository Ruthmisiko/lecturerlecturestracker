<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    public $table = 'units';

    public $fillable = [
        'user_id',
        'name',
        'unit_id'
    ];

    protected $casts = [

    ];

    public static array $rules = [

    ];

    public function classses()
{
    return $this->belongsToMany(Classs::class, 'class_unit', 'unit_id', 'classs_id');
}

}
