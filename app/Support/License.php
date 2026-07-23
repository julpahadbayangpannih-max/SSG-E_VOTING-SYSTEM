<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * A lightweight, self-hosted license key system.
 *
 * There's no license server to call — the valid key for a given install is
 * derived deterministically from that install's own APP_KEY, using
 * `php artisan license:generate` (see App\Console\Commands\GenerateLicenseKey).
 * Whoever controls the .env / APP_KEY of a deployment is the one who can
 * produce its correct key, which is enough to gate "this copy was activated
 * by whoever deployed it" without any external dependency or network call.
 *
 * This intentionally is NOT designed to stop a determined developer from
 * reading this file and generating their own key for their own install —
 * that's expected and fine. It's meant to gate accidental/casual reuse
 * (e.g. someone copies the repo but never goes through activation), not to
 * defeat someone willing to read source code they already legitimately have.
 */
class License
{
    /**
     * Format: 4 groups of 5 uppercase base32-ish chars, e.g. ABCDE-FGHIJ-KLMNO-PQRST
     */
    public static function expectedKey(): string
    {
        $secret = config('app.key') ?? '';
        $hash = strtoupper(hash_hmac('sha256', 'jrmsu-evoting-license-v1', $secret));
        $chars = substr($hash, 0, 20);

        return implode('-', str_split($chars, 5));
    }

    public static function normalize(string $key): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $key));
    }

    public static function isValidKey(string $key): bool
    {
        return static::normalize($key) === str_replace('-', '', static::expectedKey());
    }

    public static function isActivated(): bool
    {
        try {
            if (! Schema::hasTable('settings')) {
                return false;
            }

            return Setting::getValue('license_activated') === '1';
        } catch (Throwable $e) {
            // Fail open on the settings lookup itself (e.g. DB not reachable
            // yet during initial setup) rather than hard-locking the whole
            // app out from an infra hiccup unrelated to licensing.
            return true;
        }
    }

    public static function activate(string $key): bool
    {
        if (! static::isValidKey($key)) {
            return false;
        }

        Setting::setValue('license_activated', '1');
        Setting::setValue('license_key', static::normalize($key));

        return true;
    }

    public static function deactivate(): void
    {
        Setting::setValue('license_activated', '0');
    }
}
