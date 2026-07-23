<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads white-label settings (school name/logo/tagline) out of the
 * key-value `settings` table, with safe fallbacks. Wrapped defensively so
 * it never breaks a request/artisan command that runs before migrations —
 * e.g. `php artisan migrate` itself, or any command run on a fresh clone.
 */
class Branding
{
    protected static ?array $cache = null;

    public static function get(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $defaults = [
            'school_name' => 'JRMSU Siocon Campus',
            'school_short_name' => 'JRMSU',
            'tagline' => 'SSG Election · E-Voting System',
            'logo_url' => null,
        ];

        try {
            if (! Schema::hasTable('settings')) {
                return static::$cache = $defaults;
            }

            $logoPath = Setting::getValue('school_logo_path');

            static::$cache = [
                'school_name' => Setting::getValue('school_name', $defaults['school_name']),
                'school_short_name' => Setting::getValue('school_short_name', $defaults['school_short_name']),
                'tagline' => Setting::getValue('school_tagline', $defaults['tagline']),
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            ];
        } catch (Throwable $e) {
            static::$cache = $defaults;
        }

        return static::$cache;
    }

    /**
     * Called after Settings are updated so the new values show up
     * immediately within the same request/deploy instead of needing a
     * fresh process.
     */
    public static function forget(): void
    {
        static::$cache = null;
    }
}
