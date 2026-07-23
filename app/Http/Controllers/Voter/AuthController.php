<?php

namespace App\Http\Controllers\Voter;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Models\Voter;
use App\Services\SimpleCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use LogsActivity;

    public function showLogin()
    {
        if (Auth::guard('voter')->check()) {
            return redirect()->route('voter.dashboard');
        }
        $captchaQuestion = SimpleCaptcha::generate();

        return view('auth.voter-login', compact('captchaQuestion'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
            'captcha_answer' => ['nullable', 'string'],
        ]);

        if (! SimpleCaptcha::verify($request->captcha_answer)) {
            $this->auditLog($request, 'voter_login_failed', 'voter', null, $request->student_id, [
                'reason' => 'Failed CAPTCHA',
            ]);

            return back()->withErrors(['captcha_answer' => 'Incorrect answer. Please try the new question below.'])->withInput($request->except(['password', 'captcha_answer']));
        }

        $voter = Voter::where('student_id', $request->student_id)->first();

        if (! $voter) {
            $this->auditLog($request, 'voter_login_failed', 'voter', null, $request->student_id, [
                'reason' => 'Student ID not found',
            ]);

            return back()->withErrors(['student_id' => 'Student ID not found in the official voter list.'])->withInput();
        }

        if (! $voter->is_approved) {
            $this->auditLog($request, 'voter_login_failed', 'voter', $voter->id, $voter->name, [
                'reason' => 'Account not yet approved',
            ]);

            return back()->withErrors(['student_id' => 'Your registration is still pending admin approval.'])->withInput();
        }

        // FIX (account takeover): approved voters now always have a real
        // password already set by the admin (see VoterController::approve/store).
        // A null password here means something went wrong on the admin side —
        // there is no safe way to let the caller "claim" the account by typing
        // any password, so we fail closed instead.
        if ($voter->password === null) {
            $this->auditLog($request, 'voter_login_failed', 'voter', $voter->id, $voter->name, [
                'reason' => 'No password set for this account',
            ]);

            return back()->withErrors(['student_id' => 'Your account has no password set yet. Please contact the election administrator.'])->withInput();
        }

        if (! Hash::check($request->password, $voter->password)) {
            $this->auditLog($request, 'voter_login_failed', 'voter', $voter->id, $voter->name, [
                'reason' => 'Wrong password',
            ]);

            return back()->withErrors(['password' => 'Invalid password.'])->withInput($request->except('password'));
        }

        Auth::guard('voter')->login($voter);
        $request->session()->regenerate();
        $this->auditLog($request, 'voter_login', 'voter', $voter->id, $voter->name);

        return redirect()->route('voter.dashboard');
    }

    public function logout(Request $request)
    {
        $voter = Auth::guard('voter')->user();
        if ($voter) {
            $this->auditLog($request, 'voter_logout', 'voter', $voter->id, $voter->name);
        }
        Auth::guard('voter')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('voter.login');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'unique:voters,student_id'],
            'name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'captcha_answer' => ['nullable', 'string'],
        ]);

        if (! SimpleCaptcha::verify($request->captcha_answer)) {
            $this->auditLog($request, 'voter_registration_failed', 'voter', null, $request->student_id ?? 'unknown', [
                'reason' => 'Failed CAPTCHA',
            ]);

            return back()->withErrors(['captcha_answer' => 'Incorrect answer. Please try the new question below.'])->withInput($request->except('captcha_answer'));
        }

        // is_approved isn't mass-assignable (see Voter::$fillable) and isn't
        // passed here — new registrations rely on the column's DB default
        // (false) until an admin approves them.
        $voter = Voter::create(Arr::except($data, ['captcha_answer']));
        $this->auditLog($request, 'voter_registered', 'voter', $voter->id, $voter->name, [
            'student_id' => $voter->student_id,
            'course' => $voter->course,
        ]);

        return back()->with('success', 'Registration submitted. Please wait for admin approval before voting.');
    }
}
