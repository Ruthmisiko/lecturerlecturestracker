<?php

namespace App\Models\Concerns;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Limits every query to the logged-in user's current institution and
 * stamps new records with it. Without a logged-in user (console, migrations) nothing is filtered.
 */
trait BelongsToInstitution
{
    public static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $query) {
            if ($id = static::currentInstitutionId()) {
                $query->where($query->getModel()->getTable() . '.institution_id', $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->institution_id) && ($id = static::currentInstitutionId())) {
                $model->institution_id = $id;
            }
        });
    }

    protected static function currentInstitutionId(): ?int
    {
        return Auth::user()?->currentInstitutionId();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
