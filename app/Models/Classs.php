<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classs extends Model
{
    public $table = 'classses';

    public $fillable = [
        'user_id',
        'name',
        'code',
        'desciription',        
    ];

    protected $casts = [
        
    ];

    public static array $rules = [
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
