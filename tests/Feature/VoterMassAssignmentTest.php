<?php

namespace Tests\Feature;

use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Directly proves the model-level fix: even if some future code does
     * Voter::create($request->all()), is_approved cannot be set.
     */
    public function test_is_approved_is_not_mass_assignable(): void
    {
        $voter = Voter::create([
            'student_id' => '2025-0001',
            'name' => 'Juan Dela Cruz',
            'course' => 'BSIT',
            'password' => bcrypt('secret123'),
            'is_approved' => true,  // attacker-style extra input
        ]);

        // NOTE: create()'s returned instance won't reflect a DB-applied
        // column default for an attribute that was never part of the
        // insert payload in the first place (is_approved is excluded here
        // by the fillable guard, so it's simply absent from $voter's
        // in-memory attributes until re-read). fresh() re-queries the row
        // that was actually written, which is what we care about proving.
        $this->assertFalse($voter->fresh()->is_approved, 'is_approved should default to false, not be settable via mass assignment');
    }

    /**
     * Proves the actual public registration endpoint can't be used to
     * self-approve, even if extra fields are stuffed into the POST body.
     */
    public function test_public_registration_cannot_self_approve(): void
    {
        $this->post(route('voter.register'), [
            'student_id' => '2025-0002',
            'name' => 'Maria Clara',
            'course' => 'BSCS',
            'is_approved' => true,
        ]);

        $voter = Voter::where('student_id', '2025-0002')->firstOrFail();

        $this->assertFalse($voter->is_approved);
    }
}
