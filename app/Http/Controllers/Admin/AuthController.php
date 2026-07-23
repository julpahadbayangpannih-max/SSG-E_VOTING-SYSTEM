<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Models\Admin;
use App\Services\SimpleCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use LogsActivity;

    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        $captchaQuestion = SimpleCaptcha::generate();

        return view('auth.admin-login', compact('captchaQuestion'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha_answer' => ['nullable', 'string'],
        ]);

        if (! SimpleCaptcha::verify($request->captcha_answer)) {
            $this->auditLog($request, 'admin_login_failed', 'admin', null, $credentials['username'], [
                'reason' => 'Failed CAPTCHA',
            ]);

            return back()->withErrors(['captcha_answer' => 'Incorrect answer. Please try the new question below.'])->withInput($request->except(['password', 'captcha_answer']));
        }

        // FIX (2FA bypass risk): we no longer call Auth::guard('admin')->attempt()
        // directly, because that logs the admin in as soon as the password is
        // right — which would fully authenticate a 2FA-enabled account before
        // the OTP step ever runs. Instead we verify the password ourselves and,
        // for 2FA accounts, only stash a *pending* id in the session. The admin
        // session (Auth::guard('admin')->login()) is created in
        // TwoFactorController::verifyChallenge(), only after a valid code.
        $admin = Admin::where('username', $credentials['username'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            $this->auditLog($request, 'admin_login_failed', 'admin', null, $credentials['username'], [
                'reason' => 'Invalid credentials',
            ]);

            return back()->withErrors(['username' => 'Invalid username or password.'])->withInput($request->except('password'));
        }

        if ($admin->hasEnabledTwoFactor()) {
            $request->session()->put('admin_2fa_pending_id', $admin->id);
            $request->session()->put('admin_2fa_pending_at', time());

            return redirect()->route('admin.2fa.challenge');
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $this->auditLog($request, 'admin_login', 'admin', $admin->id, $admin->name);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            $this->auditLog($request, 'admin_logout', 'admin', $admin->id, $admin->name);
        }
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
