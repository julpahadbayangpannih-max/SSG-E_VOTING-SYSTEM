<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoterVotingTest extends TestCase
{
    use RefreshDatabase;

    private function openElection(string $title = 'Test Election'): Election
    {
        $election = Election::create([
            'title' => $title,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $election->forceFill(['status' => 'open'])->save();

        return $election;
    }

    private function makeApprovedVoter(string $studentId = '2025-0001'): Voter
    {
        return Voter::forceCreate([
            'student_id' => $studentId,
            'name' => 'Test Voter',
            'course' => 'BSIT',
            'password' => bcrypt('secret123'),
            'is_approved' => true,
        ]);
    }

    public function test_voter_cannot_vote_while_election_is_closed(): void
    {
        // No election open at all = closed by default.
        $voter = $this->makeApprovedVoter();
        $election = Election::create(['title' => 'Draft Election']); // stays 'draft'
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidate->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_voter_can_cast_a_valid_vote_while_election_is_open(): void
    {
        $election = $this->openElection();
        $voter = $this->makeApprovedVoter();
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();

        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidate->id],
        ]);

        $response->assertRedirect(route('voter.dashboard'));
        $this->assertDatabaseHas('votes', [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'election_id' => $election->id,
        ]);
        $this->assertTrue(Ballot::hasVoted($voter->id, $election->id));
    }

    public function test_vote_rows_do_not_store_who_cast_them(): void
    {
        // BALLOT SECRECY: the votes table must never carry a voter_id — that
        // would let anyone with DB access trace an individual's ballot back
        // to them. "Who voted" belongs solely to the Ballot table.
        $election = $this->openElection();
        $voter = $this->makeApprovedVoter();
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();

        $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidate->id],
        ]);

        $this->assertFalse(
            Schema::hasColumn('votes', 'voter_id'),
            'the votes table must not have a voter_id column'
        );
    }

    public function test_voter_cannot_vote_a_second_time_in_the_same_election(): void
    {
        $election = $this->openElection();
        $voter = $this->makeApprovedVoter();
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidateA = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $candidateA->forceFill(['election_id' => $election->id])->save();
        $candidateB = new Candidate(['name' => 'Candidate B', 'position_id' => $position->id]);
        $candidateB->forceFill(['election_id' => $election->id])->save();

        // First ballot goes through.
        $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidateA->id],
        ])->assertRedirect(route('voter.dashboard'));

        // Second attempt — same voter, different pick — must be blocked.
        $response = $this->actingAs($voter->fresh(), 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidateB->id],
        ]);

        $response->assertStatus(403);
        $this->assertSame(1, Vote::where('election_id', $election->id)->count(), 'only the first ballot should have been recorded');
    }

    public function test_voter_can_vote_again_in_a_newly_opened_election(): void
    {
        $firstElection = $this->openElection('First Election');
        $voter = $this->makeApprovedVoter();
        $position1 = new Position(['name' => 'President', 'max_votes' => 1]);
        $position1->forceFill(['election_id' => $firstElection->id])->save();
        $candidate1 = new Candidate(['name' => 'Candidate A', 'position_id' => $position1->id]);
        $candidate1->forceFill(['election_id' => $firstElection->id])->save();

        $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position1->id => $candidate1->id],
        ])->assertRedirect(route('voter.dashboard'));

        // Close the first election, open a brand new one.
        $firstElection->forceFill(['status' => 'closed'])->save();
        $secondElection = $this->openElection('Second Election');
        $position2 = new Position(['name' => 'President', 'max_votes' => 1]);
        $position2->forceFill(['election_id' => $secondElection->id])->save();
        $candidate2 = new Candidate(['name' => 'Candidate B', 'position_id' => $position2->id]);
        $candidate2->forceFill(['election_id' => $secondElection->id])->save();

        $response = $this->actingAs($voter->fresh(), 'voter')->post(route('voter.vote'), [
            'votes' => [$position2->id => $candidate2->id],
        ]);

        $response->assertRedirect(route('voter.dashboard'));
        $this->assertTrue(Ballot::hasVoted($voter->id, $firstElection->id));
        $this->assertTrue(Ballot::hasVoted($voter->id, $secondElection->id));
        // Only this one voter cast ballots anywhere in this test, so a total
        // count across both elections stands in for "voter_id = X" without
        // needing a voter_id column on votes (see ballot secrecy fix).
        $this->assertSame(2, Vote::count());
    }

    public function test_only_one_election_can_be_open_at_a_time(): void
    {
        $this->openElection('Already Open');
        $second = Election::create(['title' => 'Second']);

        $admin = Admin::create([
            'username' => 'admin1', 'password' => bcrypt('password123'), 'name' => 'Admin',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.elections.open', $second));

        $response->assertStatus(422);
        $this->assertSame('draft', $second->fresh()->status);
    }

    public function test_closed_election_data_is_read_only(): void
    {
        $election = Election::create(['title' => 'History']);
        $election->forceFill(['status' => 'closed'])->save();

        $admin = Admin::create([
            'username' => 'admin2', 'password' => bcrypt('password123'), 'name' => 'Admin',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->withSession(['admin_current_election_id' => $election->id])
            ->postJson(route('admin.positions.store'), [
                'name' => 'New Position', 'max_votes' => 1,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('positions', 0);
    }

    public function test_selecting_more_candidates_than_max_votes_voids_the_ballot(): void
    {
        $election = $this->openElection();
        $voter = $this->makeApprovedVoter();
        $position = new Position(['name' => 'Senator', 'max_votes' => 2]);
        $position->forceFill(['election_id' => $election->id])->save();
        $c1 = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $c1->forceFill(['election_id' => $election->id])->save();
        $c2 = new Candidate(['name' => 'Candidate B', 'position_id' => $position->id]);
        $c2->forceFill(['election_id' => $election->id])->save();
        $c3 = new Candidate(['name' => 'Candidate C', 'position_id' => $position->id]);
        $c3->forceFill(['election_id' => $election->id])->save();

        // max_votes is 2, voter tries to select all 3 — overvote.
        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => [$c1->id, $c2->id, $c3->id]],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('votes', 0);
        $this->assertFalse(Ballot::hasVoted($voter->id, $election->id));
    }

    public function test_voting_for_a_candidate_from_a_different_position_is_rejected(): void
    {
        $election = $this->openElection();
        $voter = $this->makeApprovedVoter();

        $president = new Position(['name' => 'President', 'max_votes' => 1]);
        $president->forceFill(['election_id' => $election->id])->save();
        $senator = new Position(['name' => 'Senator', 'max_votes' => 1]);
        $senator->forceFill(['election_id' => $election->id])->save();

        // Candidate actually belongs to "Senator"...
        $senatorCandidate = new Candidate(['name' => 'Candidate A', 'position_id' => $senator->id]);
        $senatorCandidate->forceFill(['election_id' => $election->id])->save();

        // ...but the request claims they're running for "President".
        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$president->id => $senatorCandidate->id],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_voting_for_a_position_outside_the_open_election_is_rejected(): void
    {
        $openElection = $this->openElection('Open One');
        $otherElection = Election::create(['title' => 'Other']);
        $voter = $this->makeApprovedVoter();

        // Position belongs to a *different* election than the one that's open.
        $foreignPosition = new Position(['name' => 'President', 'max_votes' => 1]);
        $foreignPosition->forceFill(['election_id' => $otherElection->id])->save();
        $foreignCandidate = new Candidate(['name' => 'Candidate A', 'position_id' => $foreignPosition->id]);
        $foreignCandidate->forceFill(['election_id' => $otherElection->id])->save();

        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$foreignPosition->id => $foreignCandidate->id],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('votes', 0);
    }
}
