<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = ['name', 'employer_pin', 'gemini_api_key', 'owner_id', 'logo_path', 'letterhead_path', 'primary_color', 'secondary_color'];

    /** The Gemini key is encrypted at rest and never sent out in arrays/JSON. */
    protected $casts = ['gemini_api_key' => 'encrypted'];
    protected $hidden = ['gemini_api_key'];

    /** e.g. "AQ.Ab8…n3-Q" so the saved key can be recognised without showing it. */
    public function maskedGeminiKey(): ?string
    {
        $key = $this->gemini_api_key;

        return $key ? substr($key, 0, 6) . '…' . substr($key, -4) : null;
    }

    public const DEFAULT_PRIMARY_COLOR   = '#28a745';
    public const DEFAULT_SECONDARY_COLOR = '#1e7e34';

    /** Uploaded images live on the local (private) disk and are served through the app. */
    public const ASSETS = ['logo' => 'logo_path', 'letterhead' => 'letterhead_path'];

    /** URL to show an uploaded logo/letterhead in the browser, or null if none. */
    public function assetUrl(string $type): ?string
    {
        $column = self::ASSETS[$type] ?? null;

        return $column && $this->$column
            ? route('institutions.asset', [$this->id, $type, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    /** Absolute file path (e.g. for embedding in PDFs), or null if none. */
    public function assetPath(string $type): ?string
    {
        $column = self::ASSETS[$type] ?? null;

        return $column && $this->$column && \Storage::disk('local')->exists($this->$column)
            ? \Storage::disk('local')->path($this->$column)
            : null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
