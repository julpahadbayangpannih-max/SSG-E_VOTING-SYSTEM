<?php

namespace Tests\Feature;

use App\Http\Traits\LogsActivity;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogChainTest extends TestCase
{
    // Pull in the trait directly so these tests can write entries without
    // going through a full HTTP request/controller.
    use LogsActivity;
    use RefreshDatabase;

    public function test_fresh_chain_verifies_as_intact(): void
    {
        $result = AuditLog::verifyChainIntegrity();

        $this->assertTrue($result['ok']);
        $this->assertEmpty($result['broken_entries']);
        $this->assertFalse($result['chain_state_mismatch']);
    }

    public function test_a_sequence_of_entries_verifies_as_intact(): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        $this->auditLog($request, 'voter_login', 'voter', 1, 'Test Voter');
        $this->auditLog($request, 'vote_submitted', 'voter', 1, 'Test Voter', ['election_id' => 1]);
        $this->auditLog($request, 'admin_login', 'admin', 1, 'Test Admin');

        $this->assertSame(3, AuditLog::count());

        $result = AuditLog::verifyChainIntegrity();
        $this->assertTrue($result['ok'], 'a clean sequence of writes should verify as intact');
    }

    public function test_editing_a_past_entry_is_detected(): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        $this->auditLog($request, 'voter_login', 'voter', 1, 'Test Voter');
        $this->auditLog($request, 'vote_submitted', 'voter', 1, 'Test Voter', ['election_id' => 1]);
        $this->auditLog($request, 'admin_login', 'admin', 1, 'Test Admin');

        // Simulate someone editing a row directly in the database — bypass
        // Eloquent entirely, the same way a rogue DB client would.
        $tampered = AuditLog::where('action', 'vote_submitted')->firstOrFail();
        DB::table('audit_logs')
            ->where('id', $tampered->id)
            ->update(['details' => json_encode(['election_id' => 999])]);

        $result = AuditLog::verifyChainIntegrity();

        $this->assertFalse($result['ok']);
        $this->assertContains($tampered->id, $result['broken_entries']);
        // The tampered row's own hash didn't move (only its content did),
        // so the chain after it should NOT cascade into false positives.
        $this->assertCount(1, $result['broken_entries'], 'only the tampered row should be flagged, not everything after it');
    }

    public function test_deleting_an_entry_without_a_checkpoint_is_detected(): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        $this->auditLog($request, 'voter_login', 'voter', 1, 'Test Voter');
        $this->auditLog($request, 'vote_submitted', 'voter', 1, 'Test Voter');

        // Someone deletes the last row directly, outside the
        // audit-logs:cleanup command that would normally leave a checkpoint.
        AuditLog::orderByDesc('id')->first()->delete();

        $result = AuditLog::verifyChainIntegrity();

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['chain_state_mismatch'], 'an undocumented deletion should surface as a chain-state mismatch');
    }

    public function test_legitimate_cleanup_leaves_a_verifiable_chain(): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        // Old entry that the cleanup command will consider expired.
        $this->auditLog($request, 'voter_login', 'voter', 1, 'Old Entry');
        AuditLog::query()->update(['created_at' => now()->subDays(400)]);

        // Recent entry that should survive cleanup.
        $this->auditLog($request, 'admin_login', 'admin', 1, 'Recent Entry');

        $this->artisan('audit-logs:cleanup', ['--days' => 365])->assertExitCode(0);

        $this->assertSame(1, AuditLog::count(), 'the old entry should have been purged');

        $result = AuditLog::verifyChainIntegrity();
        $this->assertTrue($result['ok'], 'a documented cleanup should still verify as intact via its checkpoint');
    }
}
