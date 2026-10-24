<?php

namespace App\Support;

use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

/**
 * Colours and logo of the institution the user is working in, with readable text colours
 * and lighter/darker shades derived from the two chosen colours.
 */
class Theme
{
    public function __construct(
        public string $primary,
        public string $secondary,
        public ?Institution $institution = null,
    ) {}

    public static function current(): self
    {
        static $cache = [];

        $id = Auth::user()?->currentInstitutionId() ?? 0;

        return $cache[$id] ??= self::for($id ? Institution::find($id) : null);
    }

    public static function for(?Institution $institution): self
    {
        $valid = fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c);

        return new self(
            $valid($institution?->primary_color) ? strtolower($institution->primary_color) : Institution::DEFAULT_PRIMARY_COLOR,
            $valid($institution?->secondary_color) ? strtolower($institution->secondary_color) : Institution::DEFAULT_SECONDARY_COLOR,
            $institution,
        );
    }

    /** White or near-black, whichever reads better on the given background. */
    public static function textOn(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $lin = fn ($c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $luminance > 0.45 ? '#212529' : '#ffffff';
    }

    /** Mix the colour with black (negative) or white (positive) by a 0–1 amount. */
    public static function shade(string $hex, float $amount): string
    {
        $target = $amount < 0 ? 0 : 255;
        $amount = abs($amount);

        return '#' . collect(self::rgb($hex))
            ->map(fn ($c) => str_pad(dechex((int) round($c + ($target - $c) * $amount)), 2, '0', STR_PAD_LEFT))
            ->join('');
    }

    public static function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }

    public function primaryText(): string   { return self::textOn($this->primary); }
    public function secondaryText(): string { return self::textOn($this->secondary); }
    public function primaryDark(): string   { return self::shade($this->primary, -0.18); }
    public function primarySoft(): string   { return self::shade($this->primary, 0.88); }
    public function secondarySoft(): string { return self::shade($this->secondary, 0.80); }

    /** "r, g, b" for use in rgba(). */
    public function primaryRgb(): string { return implode(', ', self::rgb($this->primary)); }

    public function logoUrl(): ?string  { return $this->institution?->assetUrl('logo'); }
    public function logoPath(): ?string { return $this->institution?->assetPath('logo'); }
}
