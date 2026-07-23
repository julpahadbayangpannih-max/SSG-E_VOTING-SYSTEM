<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\VerifyController;
use App\Http\Controllers\Voter;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateVoter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| License Activation (must stay outside CheckLicense middleware)
|--------------------------------------------------------------------------
*/
Route::get('/license/activate', [LicenseController::class, 'show'])->name('license.activate');
Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.activate.post')->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Public Verification ("Verify My Vote")
|--------------------------------------------------------------------------
| No auth required — a voter only needs their own receipt code. Rate
| limited to make brute-forcing receipt codes impractical on top of their
| own entropy (see Ballot::generateReceiptCode()).
*/
Route::get('/verify', [VerifyController::class, 'show'])->name('verify.show');
Route::post('/verify', [VerifyController::class, 'check'])->name('verify.check')->middleware('throttle:12,1');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Public
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');

    // Public: 2FA challenge — reached after a correct password but before the
    // admin session is created, so this must stay outside AuthenticateAdmin.
    Route::get('/2fa/challenge', [Admin\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [Admin\TwoFactorController::class, 'verifyChallenge'])->name('2fa.challenge.post')->middleware('throttle:5,1');

    // Protected
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Two-factor authentication management
        Route::get('/2fa', [Admin\TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('/2fa/enable', [Admin\TwoFactorController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/disable', [Admin\TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::post('/2fa/recovery-codes', [Admin\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery.regenerate');

        // Voters
        Route::get('/voters', [Admin\VoterController::class, 'index'])->name('voters.index');
        Route::post('/voters', [Admin\VoterController::class, 'store'])->name('voters.store');
        Route::post('/voters/import', [Admin\VoterController::class, 'import'])->name('voters.import');
        Route::put('/voters/{voter}', [Admin\VoterController::class, 'update'])->name('voters.update');
        Route::delete('/voters/{voter}', [Admin\VoterController::class, 'destroy'])->name('voters.destroy');
        Route::patch('/voters/{voter}/approve', [Admin\VoterController::class, 'approve'])->name('voters.approve');
        Route::delete('/voters/{voter}/reject', [Admin\VoterController::class, 'reject'])->name('voters.reject');

        // Positions
        Route::get('/positions', [Admin\PositionController::class, 'index'])->name('positions.index');
        Route::post('/positions', [Admin\PositionController::class, 'store'])->name('positions.store');
        Route::put('/positions/{position}', [Admin\PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [Admin\PositionController::class, 'destroy'])->name('positions.destroy');

        // Candidates
        Route::get('/candidates', [Admin\CandidateController::class, 'index'])->name('candidates.index');
        Route::post('/candidates', [Admin\CandidateController::class, 'store'])->name('candidates.store');
        Route::post('/candidates/{candidate}', [Admin\CandidateController::class, 'update'])->name('candidates.update'); // POST for file upload support
        Route::delete('/candidates/{candidate}', [Admin\CandidateController::class, 'destroy'])->name('candidates.destroy');

        // Results & Reset
        Route::get('/results', [Admin\ResultController::class, 'index'])->name('results.index');
        Route::post('/reset-votes', [Admin\ResultController::class, 'resetVotes'])->name('results.reset');

        // Elections
        Route::get('/elections', [Admin\ElectionController::class, 'index'])->name('elections.index');
        Route::post('/elections', [Admin\ElectionController::class, 'store'])->name('elections.store');
        Route::put('/elections/{election}', [Admin\ElectionController::class, 'update'])->name('elections.update');
        Route::post('/elections/{election}/open', [Admin\ElectionController::class, 'open'])->name('elections.open');
        Route::post('/elections/{election}/close', [Admin\ElectionController::class, 'close'])->name('elections.close');
        Route::post('/elections/{election}/switch', [Admin\ElectionController::class, 'switch'])->name('elections.switch');
        Route::delete('/elections/{election}', [Admin\ElectionController::class, 'destroy'])->name('elections.destroy');

        // Export
        Route::get('/results/export/csv', [Admin\ResultController::class, 'exportCsv'])->name('results.export.csv');
        Route::get('/results/export/pdf', [Admin\ResultController::class, 'exportPdfView'])->name('results.export.pdf');

        // Audit Logs
        Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        // White-label / branding settings
        Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});

/*
|--------------------------------------------------------------------------
| Voter Routes
|--------------------------------------------------------------------------
*/
Route::prefix('voter')->name('voter.')->group(function () {

    // Public
    Route::get('/login', [Voter\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Voter\AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::post('/register', [Voter\AuthController::class, 'register'])->name('register')->middleware('throttle:10,1');

    // Public: live results JSON (used by voter dashboard after voting)
    Route::get('/results', [Voter\VoterDashboardController::class, 'liveResults'])->name('results');

    // Protected
    Route::middleware(AuthenticateVoter::class)->group(function () {
        Route::post('/logout', [Voter\AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [Voter\VoterDashboardController::class, 'index'])->name('dashboard');
        // SECURITY: was previously unthrottled — the only mutating,
        // auth-protected route in the app without a rate limit. Double-
        // voting was already impossible (locked transaction + unique
        // constraint), but nothing stopped a compromised/scripted session
        // from hammering this endpoint. 8/min is generous for a real voter
        // (who submits once) while blocking scripted abuse.
        Route::post('/vote', [Voter\VoterDashboardController::class, 'submitVote'])->name('vote')->middleware('throttle:8,1');
    });
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/ping', function () {
    return response('OK', 200);
});
