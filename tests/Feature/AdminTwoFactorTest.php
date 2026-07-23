<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_without_2fa_goes_straight_to_dashboard(): void
    {
        $admin = Admin::create([
            'username' => 'plainadmin',
            'password' => bcrypt('password123'),
            'name' => 'Plain Admin',
        ]);

        $response = $this->post(route('admin.login.post'), [
            'username' => 'plainadmin',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_correct_password_alone_does_not_log_in_a_2fa_admin(): void
    {
        $admin = $this->makeTwoFactorAdmin('2fa_admin', 'password123', $secret);

        $response = $this->post(route('admin.login.post'), [
            'username' => '2fa_admin',
            'password' => 'password123',
        ]);

        // This is the whole point of the fix: password alone is NOT enough
        // once 2FA is on. No admin session should exist yet.
        $response->assertRedirect(route('admin.2fa.challenge'));
        $this->assertGuest('admin');
    }

    public function test_wrong_otp_code_is_rejected(): void
    {
        $this->makeTwoFactorAdmin('wrongcode_admin', 'password123', $secret);

        $this->post(route('admin.login.post'), [
            'username' => 'wrongcode_admin',
            'password' => 'password123',
        ]);

        $response = $this->post(route('admin.2fa.challenge.post'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_correct_otp_code_completes_login(): void
    {
        $admin = $this->makeTwoFactorAdmin('correctcode_admin', 'password123', $secret);

        $this->post(route('admin.login.post'), [
            'username' => 'correctcode_admin',
            'password' => 'password123',
        ]);

        $validCode = $this->currentCodeFor($secret);

        $response = $this->post(route('admin.2fa.challenge.post'), ['code' => $validCode]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_recovery_code_logs_in_and_is_burned_after_use(): void
    {
        $admin = $this->makeTwoFactorAdmin('recovery_admin', 'password123', $secret);
        // FIX: same guarded-field issue as makeTwoFactorAdmin() — ->update()
        // silently no-ops on two_factor_recovery_codes, forceFill() is correct.
        $admin->forceFill(['two_factor_recovery_codes' => ['ABCD-1234']])->save();

        $this->post(route('admin.login.post'), [
            'username' => 'recovery_admin',
            'password' => 'password123',
        ]);

        $response = $this->post(route('admin.2fa.challenge.post'), ['code' => 'ABCD-1234']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertEmpty($admin->fresh()->two_factor_recovery_codes, 'recovery code should be single-use');
    }

    /**
     * @param string|null $secret Passed by reference so the caller can
     *                            generate a matching OTP later.
     */
    private function makeTwoFactorAdmin(string $username, string $password, ?string &$secret): Admin
    {
        $totp = new TwoFactorAuthenticator;
        $secret = $totp->generateSecret();

        $admin = Admin::create([
            'username' => $username,
            'password' => bcrypt($password),
            'name' => 'Test Admin',
        ]);

        // FIX: ->update() silently no-ops here — two_factor_secret and
        // two_factor_confirmed_at are deliberately guarded (not fillable) on
        // the Admin model, same protection we rely on in TwoFactorController.
        // forceFill() is the correct way to set them from trusted test code.
        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $admin;
    }

    private function currentCodeFor(string $secret): string
    {
        $totp = new TwoFactorAuthenticator;
        $ref = new ReflectionClass($totp);
        $method = $ref->getMethod('codeAt');
        $method->setAccessible(true);

        return $method->invoke($totp, $secret, intdiv(time(), 30));
    }
}
