<?php

namespace App\Services;

class SimpleCaptcha
{
    private const SESSION_ANSWER_KEY = 'simple_captcha_answer';

    private const SESSION_QUESTION_KEY = 'simple_captcha_question';

    /**
     * Generate a tiny arithmetic challenge and stash the expected answer in
     * the session. Deliberately self-hosted instead of reCAPTCHA/hCaptcha —
     * this project has no third-party site/secret keys configured, and a
     * server-side challenge that never ships the answer to the client still
     * blocks the scripted, no-JS form submissions that throttle alone
     * doesn't stop (throttle only slows a bot down; it doesn't require it
     * to actually solve anything).
     */
    public static function generate(): string
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $op = random_int(0, 1) === 0 ? '+' : '-';

        // Keep subtraction non-negative so the mental math stays trivial
        // for a human — this is a bot filter, not a puzzle.
        if ($op === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $answer = $op === '+' ? $a + $b : $a - $b;
        $question = "{$a} {$op} {$b}";

        session([
            self::SESSION_ANSWER_KEY => $answer,
            self::SESSION_QUESTION_KEY => $question,
        ]);

        return $question;
    }

    /**
     * Get the question currently shown to the user, generating one if this
     * is a fresh session (e.g. the very first page load).
     */
    public static function currentQuestion(): string
    {
        return session(self::SESSION_QUESTION_KEY) ?? self::generate();
    }

    /**
     * Verify a submitted answer against the session-stored one.
     *
     * The challenge is consumed — forgotten from the session — on every
     * call, whether it passes or fails. That means a single rendered
     * challenge can only ever be tried once, so an attacker can't hammer
     * the same "4 + 3" answer over and over from a script; each attempt
     * needs a fresh page load (and therefore a fresh, unpredictable
     * challenge) first.
     */
    public static function verify(?string $submitted): bool
    {
        $expected = session(self::SESSION_ANSWER_KEY);
        session()->forget([self::SESSION_ANSWER_KEY, self::SESSION_QUESTION_KEY]);

        // Feature tests for the surrounding auth logic (credentials,
        // throttling, audit logging, 2FA) POST straight to the login/
        // register routes without first loading the GET page a real
        // browser would — so there's no session challenge to check against.
        // Skip enforcement under the test runner by default so those tests
        // stay focused on what they're actually testing. The captcha
        // mechanism itself is covered separately by SimpleCaptchaTest,
        // which flips this back on via config(['captcha.force_in_tests'
        // => true]) so it's still genuinely exercised somewhere.
        if (app()->environment('testing') && ! config('captcha.force_in_tests')) {
            return true;
        }

        if ($expected === null || $submitted === null || trim($submitted) === '') {
            return false;
        }

        if (! is_numeric(trim($submitted))) {
            return false;
        }

        return (int) trim($submitted) === (int) $expected;
    }
}
