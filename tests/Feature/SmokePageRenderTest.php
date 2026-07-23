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

class SmokePageRenderTest extends TestCase
{
    use RefreshDatabase;

    private function seedFullElection(): array
    {
        $election = Election::create([
            'title' => 'Smoke Test Election',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $election->forceFill(['status' => 'open'])->save();

        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();

        $candidate = new Candidate(['name' => 'Juan Dela Cruz', 'party_list' => 'Independent', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();

        $voter = Voter::forceCreate([
            'student_id' => '2025-9999',
            'name' => 'Smoke Voter',
            'course' => 'BSIT',
            'password' => bcrypt('secret123'),
            'is_approved' => true,
        ]);

        (new Vote(['position_id' => $position->id, 'candidate_id' => $candidate->id]))
            ->forceFill(['election_id' => $election->id])->save();

        $pendingVoter = Voter::forceCreate([
            'student_id' => '2025-8888',
            'name' => 'Pending Voter',
            'course' => 'BSCS',
            'is_approved' => false,
        ]);

        return compact('election', 'position', 'candidate', 'voter', 'pendingVoter');
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('voter.login'))->assertOk();
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_voter_dashboard_renders_with_and_without_open_election(): void
    {
        $data = $this->seedFullElection();

        $this->actingAs($data['voter'], 'voter')
            ->get(route('voter.dashboard'))
            ->assertOk();

        // Also with no election at all (fresh install state).
        $freshVoter = Voter::forceCreate([
            'student_id' => '2025-7777', 'name' => 'Fresh', 'course' => 'BSIT',
            'password' => bcrypt('x'), 'is_approved' => true,
        ]);
        $this->actingAs($freshVoter, 'voter')
            ->get(route('voter.dashboard'))
            ->assertOk();
    }

    public function test_all_admin_pages_render_with_seeded_data(): void
    {
        $admin = Admin::create(['username' => 'smoke_admin', 'password' => bcrypt('password123'), 'name' => 'Smoke Admin']);
        $data = $this->seedFullElection();

        $client = $this->actingAs($admin, 'admin')
            ->withSession(['admin_current_election_id' => $data['election']->id]);

        $client->get(route('admin.dashboard'))->assertOk();
        $client->get(route('admin.voters.index'))->assertOk();
        $client->get(route('admin.positions.index'))->assertOk();
        $client->get(route('admin.candidates.index'))->assertOk();
        $client->get(route('admin.results.index'))->assertOk();
        $client->get(route('admin.elections.index'))->assertOk();
        $client->get(route('admin.audit-logs.index'))->assertOk();
        $client->get(route('admin.results.export.csv'))->assertOk();
    }

    public function test_admin_pages_render_with_no_election_selected_yet(): void
    {
        // Fresh install: admin logs in, no election created/selected yet.
        // This is the state most likely to trip "undefined variable" or
        // null-property-access bugs in views that assume an election exists.
        $admin = Admin::create(['username' => 'fresh_admin', 'password' => bcrypt('password123'), 'name' => 'Fresh Admin']);

        $client = $this->actingAs($admin, 'admin');

        $client->get(route('admin.dashboard'))->assertOk();
        $client->get(route('admin.voters.index'))->assertOk();
        $client->get(route('admin.positions.index'))->assertOk();
        $client->get(route('admin.candidates.index'))->assertOk();
        $client->get(route('admin.results.index'))->assertOk();
        $client->get(route('admin.elections.index'))->assertOk();
        $client->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_results_pdf_export_renders(): void
    {
        $admin = Admin::create(['username' => 'pdf_admin', 'password' => bcrypt('password123'), 'name' => 'PDF Admin']);
        $data = $this->seedFullElection();

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_current_election_id' => $data['election']->id])
            ->get(route('admin.results.export.pdf'))
            ->assertOk();
    }

    public function test_admin_2fa_setup_page_renders(): void
    {
        $admin = Admin::create(['username' => '2fa_admin', 'password' => bcrypt('password123'), 'name' => '2FA Admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.2fa.setup'))
            ->assertOk();
    }
}
