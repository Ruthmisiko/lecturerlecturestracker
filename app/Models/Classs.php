<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classs extends Model
{
    public $table = 'classses';

    public $fillable = [
        'name',
        'code',
        'desciription',        
    ];

    protected $casts = [
        
    ];

    public static array $rules = [
        
    ];

    
}
