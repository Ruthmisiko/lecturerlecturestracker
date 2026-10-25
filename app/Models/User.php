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
        'institution_id',
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

    public function departments()
    {
        return $this->belongsToMany(\App\Models\Department::class)->withTimestamps();
    }

    protected ?array $assignedDepartmentIds = null;

    /** IDs of the departments this user is restricted to (empty = no restriction). */
    public function assignedDepartmentIds(): array
    {
        return $this->assignedDepartmentIds ??= $this->departments()
            ->pluck('departments.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /** Institutions this user may work in: superusers see all of the account's, others only their own. */
    public function accessibleInstitutions()
    {
        $query = Institution::where('owner_id', $this->ownerId())->where('is_active', true)->orderBy('name');

        if (!$this->isSuperAdmin() && $this->institution_id) {
            $query->where('id', $this->institution_id);
        }

        return $query;
    }

    protected ?int $currentInstitutionId = null;

    /**
     * The institution whose data the user is working in.
     * Superusers can switch (session); everyone else is locked to their assigned institution.
     */
    public function currentInstitutionId(): ?int
    {
        if ($this->currentInstitutionId !== null) {
            return $this->currentInstitutionId;
        }

        // The system owner sits above institutions (owner console reads across them explicitly).
        if ($this->is_system_owner) {
            return $this->currentInstitutionId = 0;
        }

        $allowed = $this->accessibleInstitutions()->pluck('id')->map(fn ($id) => (int) $id);

        // Only create a first institution for brand-new accounts (never when the existing ones were deactivated).
        if ($allowed->isEmpty() && !Institution::where('owner_id', $this->ownerId())->exists()) {
            $allowed = collect([Institution::create(['name' => 'Main Institution', 'owner_id' => $this->ownerId()])->id]);
        }

        $preferred = $this->isSuperAdmin() ? (int) session('active_institution_id') : 0;

        foreach ([$preferred, (int) $this->institution_id] as $id) {
            if ($id && $allowed->contains($id)) {
                return $this->currentInstitutionId = $id;
            }
        }

        // No usable institution: 0 matches no records (never fall back to showing everything).
        return $this->currentInstitutionId = $allowed->first() ?? 0;
    }

    /** Superusers can use every feature; others need the permission (granted to their role on User Roles). */
    public function hasAccess(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        try {
            return $this->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }
    }

    /** Super admin has no parent user (user_id IS NULL) or holds the superuser role. */
    public function isSuperAdmin(): bool
    {
        return is_null($this->user_id) || $this->hasRole('superuser');
    }

    /** Walks up the created-by chain to the top-level account that owns all the data. */
    public function ownerId(): int
    {
        $owner = $this;
        $seen  = [$owner->id];

        while ($owner->user_id && !in_array($owner->user_id, $seen)) {
            $parent = User::find($owner->user_id);
            if (!$parent) break;
            $seen[] = $parent->id;
            $owner  = $parent;
        }

        return $owner->id;
    }

    /**
     * Returns the department IDs to filter data by, or null for no restriction.
     * The header switcher narrows to one department, but never outside the user's assigned ones.
     */
    public function scopedDepartmentIds(): ?array
    {
        $assigned = $this->assignedDepartmentIds();
        $active   = (int) session('active_department_id');

        if ($active && (empty($assigned) || in_array($active, $assigned))) {
            return [$active];
        }

        return $assigned ?: null;
    }
}
