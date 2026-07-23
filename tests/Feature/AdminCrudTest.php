<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $username = 'crud_admin'): Admin
    {
        return Admin::create([
            'username' => $username,
            'password' => bcrypt('password123'),
            'name' => 'CRUD Admin',
        ]);
    }

    // ---------------------------------------------------------------
    // Elections
    // ---------------------------------------------------------------

    public function test_admin_can_create_an_election_in_draft_status(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.elections.store'), [
            'title' => 'SSG Elections 2026',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('elections', ['title' => 'SSG Elections 2026', 'status' => 'draft']);
    }

    public function test_election_status_is_not_settable_from_the_store_request(): void
    {
        // Guards against Election::create($request->all()) letting a caller
        // skip straight to 'open' or 'closed' — status must stay guarded.
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.elections.store'), [
            'title' => 'Sneaky Election',
            'status' => 'open',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('elections', ['title' => 'Sneaky Election', 'status' => 'draft']);
    }

    public function test_admin_cannot_delete_an_election_with_cast_votes(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'History']);
        $election->forceFill(['status' => 'open'])->save();
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();
        (new Vote(['position_id' => $position->id, 'candidate_id' => $candidate->id]))
            ->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.elections.destroy', $election));

        $response->assertStatus(422);
        $this->assertDatabaseHas('elections', ['id' => $election->id]);
    }

    // ---------------------------------------------------------------
    // Positions
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_position_for_the_current_election(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'E1']);

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['admin_current_election_id' => $election->id])
            ->postJson(route('admin.positions.store'), ['name' => 'President', 'max_votes' => 1]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('positions', ['name' => 'President', 'election_id' => $election->id]);
    }

    public function test_admin_cannot_delete_a_position_with_cast_votes(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'E1']);
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();
        (new Vote(['position_id' => $position->id, 'candidate_id' => $candidate->id]))
            ->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.positions.destroy', $position));

        $response->assertStatus(422);
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_admin_can_delete_a_position_with_no_votes(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'E1']);
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.positions.destroy', $position));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    // ---------------------------------------------------------------
    // Candidates
    // ---------------------------------------------------------------

    public function test_admin_cannot_attach_a_candidate_to_a_position_from_another_election(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'E1']);
        $otherElection = Election::create(['title' => 'E2']);
        $foreignPosition = new Position(['name' => 'Senator', 'max_votes' => 1]);
        $foreignPosition->forceFill(['election_id' => $otherElection->id])->save();

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['admin_current_election_id' => $election->id])
            ->postJson(route('admin.candidates.store'), [
                'name' => 'Sneaky Candidate',
                'position_id' => $foreignPosition->id,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('candidates', ['name' => 'Sneaky Candidate']);
    }

    public function test_admin_cannot_delete_a_candidate_with_cast_votes(): void
    {
        $admin = $this->makeAdmin();
        $election = Election::create(['title' => 'E1']);
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();
        (new Vote(['position_id' => $position->id, 'candidate_id' => $candidate->id]))
            ->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.candidates.destroy', $candidate));

        $response->assertStatus(422);
        $this->assertDatabaseHas('candidates', ['id' => $candidate->id]);
    }

    // ---------------------------------------------------------------
    // Voters
    // ---------------------------------------------------------------

    public function test_admin_added_voter_is_approved_with_a_usable_temp_password(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.voters.store'), [
            'student_id' => '2025-1111',
            'name' => 'Juan Dela Cruz',
            'course' => 'BSIT',
        ]);

        $response->assertOk()->assertJsonStructure(['success', 'temp_password']);
        $voter = Voter::where('student_id', '2025-1111')->firstOrFail();
        $this->assertTrue($voter->is_approved, 'admin-added voters should be pre-approved');
        $this->assertNotNull($voter->password);
    }

    public function test_approving_a_pending_voter_sets_is_approved_and_returns_a_temp_password(): void
    {
        $admin = $this->makeAdmin();
        $voter = Voter::forceCreate([
            'student_id' => '2025-2222',
            'name' => 'Pending Voter',
            'course' => 'BSCS',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.voters.approve', $voter));

        $response->assertOk()->assertJsonStructure(['success', 'temp_password']);
        $this->assertTrue($voter->fresh()->is_approved);
        $this->assertNotNull($voter->fresh()->password);
    }

    public function test_rejecting_a_pending_voter_deletes_the_registration(): void
    {
        $admin = $this->makeAdmin();
        $voter = Voter::forceCreate([
            'student_id' => '2025-3333',
            'name' => 'Rejected Voter',
            'course' => 'BSIT',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.voters.reject', $voter));

        $response->assertOk();
        $this->assertDatabaseMissing('voters', ['id' => $voter->id]);
    }

    public function test_unauthenticated_request_cannot_reach_any_admin_crud_route(): void
    {
        $election = Election::create(['title' => 'E1']);

        $this->postJson(route('admin.positions.store'), ['name' => 'X', 'max_votes' => 1])
            ->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.elections.store'), ['title' => 'X'])
            ->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.audit-logs.index'))
            ->assertRedirect(route('admin.login'));
    }
}
