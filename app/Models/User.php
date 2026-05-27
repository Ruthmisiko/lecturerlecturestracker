<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_id',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // User.php
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    /** Returns the active department scope: session switcher takes priority, then DB assignment. */
    public function scopedDepartmentId(): ?int
    {
        if (session()->has('active_department_id')) {
            $id = session('active_department_id');
            return $id ? (int) $id : null;
        }
        return $this->department_id ?? null;
    }
}
