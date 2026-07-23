<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Models\Admin;
use App\Services\TwoFactorAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    use LogsActivity;

    // How long a "correct password, waiting for OTP" session may sit idle
    // before we make the admin start the login over. Keeps a stolen/left-open
    // challenge screen from being usable indefinitely.
    private const CHALLENGE_TTL_SECONDS = 300;

    public function __construct(private TwoFactorAuthenticator $totp) {}

    private function admin(): Admin
    {
        return Auth::guard('admin')->user();
    }

    /**
     * Management page (protected route — admin is already fully logged in).
     * If 2FA isn't enabled yet, generates a pending secret held only in the
     * session (never written to the DB) until the admin confirms it works.
     */
    public function setup(Request $request)
    {
        $admin = $this->admin();

        if ($admin->hasEnabledTwoFactor()) {
            return view('admin.two-factor', ['enabled' => true]);
        }

        $secret = $request->session()->get('2fa_pending_secret');
        if (! $secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('2fa_pending_secret', $secret);
        }

        return view('admin.two-factor', [
            'enabled' => false,
            'secret' => $secret,
            'qrUri' => $this->totp->getQrCodeUri($secret, $admin->username),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $admin = $this->admin();
        $secret = $request->session()->get('2fa_pending_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Your setup session expired. Please reload this page and scan the QR code again.']);
        }

        if (! $this->totp->verify($secret, $request->input('code'))) {
            $this->auditLog($request, 'admin_2fa_setup_failed', 'admin', $admin->id, $admin->name);

            return back()->withErrors(['code' => 'Invalid code. Check that your authenticator app\'s clock is correct and try again.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        // NOTE: $admin->update([...]) would silently do nothing here — these
        // three columns are deliberately excluded from Admin::$fillable (see
        // the model), and Eloquent blocks any key not listed there once
        // $fillable is a non-empty array, regardless of $guarded. forceFill()
        // bypasses that guard for this trusted, server-controlled write.
        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('2fa_pending_secret');
        $request->session()->flash('2fa_recovery_codes_once', $recoveryCodes);

        $this->auditLog($request, 'admin_2fa_enabled', 'admin', $admin->id, $admin->name);

        return redirect()->route('admin.2fa.setup')
            ->with('success', 'Two-factor authentication is now ON. Save the recovery codes below now — they will not be shown again.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $admin = $this->admin();

        if (! Hash::check($request->input('password'), $admin->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        // Same guard issue as enable() above.
        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->auditLog($request, 'admin_2fa_disabled', 'admin', $admin->id, $admin->name);

        return redirect()->route('admin.2fa.setup')->with('success', 'Two-factor authentication has been turned off.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $admin = $this->admin();

        if (! $admin->hasEnabledTwoFactor()) {
            abort(403);
        }

        if (! Hash::check($request->input('password'), $admin->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $admin->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();
        $request->session()->flash('2fa_recovery_codes_once', $recoveryCodes);

        $this->auditLog($request, 'admin_2fa_recovery_codes_regenerated', 'admin', $admin->id, $admin->name);

        return redirect()->route('admin.2fa.setup')
            ->with('success', 'New recovery codes generated. The old ones no longer work — save these now.');
    }

    /**
     * Public: reached right after a correct username/password, before the
     * admin session actually exists. See Admin\AuthController::login().
     */
    public function showChallenge(Request $request)
    {
        if (! $this->pendingAdmin($request)) {
            return redirect()->route('admin.login')->withErrors(['username' => 'Please sign in again.']);
        }

        return view('auth.admin-2fa-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $admin = $this->pendingAdmin($request);

        if (! $admin) {
            return redirect()->route('admin.login')->withErrors(['username' => 'Your login session expired. Please sign in again.']);
        }

        $request->validate(['code' => ['required', 'string']]);
        $code = trim($request->input('code'));

        $usedRecoveryCode = false;
        $ok = $this->totp->verify($admin->two_factor_secret, $code);

        if (! $ok) {
            $usedRecoveryCode = $this->consumeRecoveryCode($admin, $code);
            $ok = $usedRecoveryCode;
        }

        if (! $ok) {
            $this->auditLog($request, 'admin_2fa_challenge_failed', 'admin', $admin->id, $admin->name);

            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_pending_at']);

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        $this->auditLog($request, 'admin_login', 'admin', $admin->id, $admin->name, [
            'via' => $usedRecoveryCode ? 'two_factor_recovery_code' : 'two_factor',
        ]);

        if ($usedRecoveryCode) {
            $remaining = is_array($admin->two_factor_recovery_codes) ? count($admin->two_factor_recovery_codes) : 0;

            return redirect()->intended(route('admin.dashboard'))
                ->with('error', "You signed in with a recovery code ({$remaining} left). Generate new codes from Security settings soon.");
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pendingAdmin(Request $request): ?Admin
    {
        $id = $request->session()->get('admin_2fa_pending_id');
        $issuedAt = $request->session()->get('admin_2fa_pending_at');

        if (! $id || ! $issuedAt || (time() - $issuedAt) > self::CHALLENGE_TTL_SECONDS) {
            $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_pending_at']);

            return null;
        }

        return Admin::find($id);
    }

    private function consumeRecoveryCode(Admin $admin, string $code): bool
    {
        $codes = $admin->two_factor_recovery_codes ?? [];
        $code = strtoupper($code);

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $admin->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$code])),
        ])->save();

        return true;
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        // Excludes 0/O/1/I to avoid transcription mistakes when an admin
        // copies these down by hand.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = '';
            for ($j = 0; $j < 8; $j++) {
                $raw .= $alphabet[random_int(0, $max)];
            }
            $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
        }

        return $codes;
    }
}
