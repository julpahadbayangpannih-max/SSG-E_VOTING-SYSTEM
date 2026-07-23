<?php

namespace App\Services;

/**
 * Minimal, dependency-free TOTP implementation (RFC 6238 / RFC 4226).
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 *
 * We hand-roll this instead of pulling in a composer package so the app has
 * one less third-party dependency to trust/patch for something this small
 * and well-specified.
 */
class TwoFactorAuthenticator
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    /**
     * Generate a new random base32 secret (160 bits of entropy).
     */
    public function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /**
     * Build the otpauth:// URI used to render the QR code / manual key.
     */
    public function getQrCodeUri(string $secret, string $accountLabel, string $issuer = 'JRMSU E-Voting'): string
    {
        $label = rawurlencode($issuer . ':' . $accountLabel);

        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * Verify a 6-digit code, allowing a small window of clock drift
     * (checks the current 30s step plus one step before/after = ~90s total).
     */
    public function verify(?string $secret, ?string $code, int $window = 1): bool
    {
        if (! $secret || ! $code) {
            return false;
        }

        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $currentStep = intdiv(time(), self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeAt($secret, $currentStep + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeAt(string $secret, int $counter): string
    {
        $secretBytes = self::base32Decode($secret);
        $counterBytes = pack('N*', 0, $counter); // 8-byte big-endian counter

        $hash = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        $offset = ord($hash[19]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $truncated % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0');
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));

        $bits = '';
        foreach (str_split($b32) as $char) {
            $val = strpos(self::BASE32_ALPHABET, $char);
            if ($val === false) {
                continue;
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
