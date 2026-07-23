<?php

namespace Tests\Feature;

use App\Models\Voter;
use App\Services\SimpleCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Other feature tests deliberately skip captcha enforcement (see
        // SimpleCaptcha::verify()) so they can POST straight to auth routes
        // without simulating a real browser's GET-then-POST flow. These
        // tests are specifically about the captcha mechanism, so they turn
        // enforcement back on.
        config(['captcha.force_in_tests' => true]);
    }

    public function test_correct_answer_passes_and_consumes_the_challenge(): void
    {
        session([
            'simple_captcha_answer' => 7,
            'simple_captcha_question' => '4 + 3',
        ]);

        $this->assertTrue(SimpleCaptcha::verify('7'));
        // Consumed — a second attempt against the same session must fail
        // even with the right number, closing the replay/brute-force gap.
        $this->assertFalse(SimpleCaptcha::verify('7'));
    }

    public function test_wrong_answer_fails_and_still_consumes_the_challenge(): void
    {
        session([
            'simple_captcha_answer' => 7,
            'simple_captcha_question' => '4 + 3',
        ]);

        $this->assertFalse(SimpleCaptcha::verify('2'));
        $this->assertNull(session('simple_captcha_answer'), 'challenge should be forgotten even after a wrong attempt');
    }

    public function test_missing_answer_fails(): void
    {
        session(['simple_captcha_answer' => 7]);

        $this->assertFalse(SimpleCaptcha::verify(null));
        $this->assertFalse(SimpleCaptcha::verify(''));
    }

    public function test_non_numeric_answer_fails_without_crashing(): void
    {
        session(['simple_captcha_answer' => 7]);

        $this->assertFalse(SimpleCaptcha::verify('seven'));
    }

    public function test_voter_login_rejects_wrong_captcha_even_with_correct_credentials(): void
    {
        $voter = Voter::forceCreate([
            'student_id' => '2025-0001',
            'name' => 'Test Voter',
            'course' => 'BSIT',
            'password' => bcrypt('secret123'),
            'is_approved' => true,
        ]);

        // Load the real login page first, same as a browser would, so a
        // genuine challenge exists in the session.
        $this->get(route('voter.login'));

        $response = $this->post(route('voter.login.post'), [
            'student_id' => '2025-0001',
            'password' => 'secret123',
            'captcha_answer' => '999999', // deliberately wrong
        ]);

        $response->assertSessionHasErrors('captcha_answer');
        $this->assertGuest('voter');
    }

    public function test_voter_registration_rejects_wrong_captcha(): void
    {
        $this->get(route('voter.login'));

        $this->post(route('voter.register'), [
            'student_id' => '2025-0099',
            'name' => 'Bot Attempt',
            'course' => 'BSIT',
            'captcha_answer' => '999999',
        ]);

        $this->assertDatabaseMissing('voters', ['student_id' => '2025-0099']);
    }
}
