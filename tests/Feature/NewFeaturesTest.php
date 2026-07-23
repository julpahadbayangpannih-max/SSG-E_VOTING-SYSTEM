<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Vote;
use App\Models\Voter;
use App\Support\Branding;
use App\Support\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
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

    private function makeAdmin(): Admin
    {
        return Admin::create([
            'username' => 'admin_' . uniqid(),
            'name' => 'Test Admin',
            'password' => bcrypt('secret123'),
        ]);
    }

    // --- Voter Receipt Code ---

    public function test_voting_generates_a_unique_receipt_code_on_the_ballot_only(): void
    {
        $election = $this->openElection();
        $position = new Position(['name' => 'President', 'max_votes' => 1]);
        $position->forceFill(['election_id' => $election->id])->save();
        $candidate = new Candidate(['name' => 'Candidate A', 'position_id' => $position->id]);
        $candidate->forceFill(['election_id' => $election->id])->save();
        $voter = $this->makeApprovedVoter();

        $response = $this->actingAs($voter, 'voter')->post(route('voter.vote'), [
            'votes' => [$position->id => $candidate->id],
        ]);

        $response->assertRedirect(route('voter.dashboard'));
        $response->assertSessionHas('receipt_code');

        $ballot = Ballot::where('voter_id', $voter->id)->where('election_id', $election->id)->first();
        $this->assertNotNull($ballot->receipt_code);
        $this->assertStringStartsWith('JRMSU-', $ballot->receipt_code);

        // Ballot secrecy: the receipt code must never leak onto Vote rows.
        $this->assertArrayNotHasKey('receipt_code', Vote::first()->getAttributes());
    }

    public function test_receipt_codes_are_unique_across_many_ballots(): void
    {
        $codes = [];
        for ($i = 0; $i < 25; $i++) {
            $codes[] = Ballot::generateReceiptCode();
        }

        $this->assertCount(25, array_unique($codes));
    }

    // --- Bulk CSV Import ---

    public function test_bulk_csv_import_creates_approved_voters_and_skips_bad_rows(): void
    {
        $admin = $this->makeAdmin();

        // Pre-existing voter so the "already exists" skip path is exercised.
        $this->makeApprovedVoter('2025-0002');

        $csv = "student_id,name,course\n"
            . "2025-0002,Duplicate,BSIT\n" // already exists -> skipped
            . "2025-0003,Juan Dela Cruz,BSIS\n" // valid -> created
            . ",Missing Id,BSA\n" // missing field -> skipped
            . "2025-0004,Maria Santos,BSCS\n"; // valid -> created

        $file = UploadedFile::fake()->createWithContent('voters.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.voters.import'), [
            'csv_file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $data = $response->json();

        $this->assertCount(2, $data['created']);
        $this->assertCount(2, $data['skipped']);
        $this->assertDatabaseHas('voters', ['student_id' => '2025-0003', 'is_approved' => true]);
        $this->assertDatabaseHas('voters', ['student_id' => '2025-0004', 'is_approved' => true]);
        // The pre-existing voter must not have been touched/duplicated.
        $this->assertDatabaseCount('voters', 3);
    }

    public function test_bulk_csv_import_rejects_a_file_with_the_wrong_headers(): void
    {
        $admin = $this->makeAdmin();

        $csv = "id,fullname\n1,Someone\n";
        $file = UploadedFile::fake()->createWithContent('voters.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.voters.import'), [
            'csv_file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    // --- License ---

    public function test_a_freshly_generated_key_is_valid_and_activates(): void
    {
        $key = License::expectedKey();

        $this->assertTrue(License::isValidKey($key));
        $this->assertTrue(License::activate($key));
        $this->assertTrue(License::isActivated());
    }

    public function test_a_wrong_key_does_not_activate(): void
    {
        $this->assertFalse(License::isValidKey('WRONG-WRONG-WRONG-WRONG'));
        $this->assertFalse(License::activate('WRONG-WRONG-WRONG-WRONG'));
        $this->assertFalse(License::isActivated());
    }

    // --- White-label branding ---

    public function test_admin_can_update_branding_and_it_reflects_in_helper(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $logo = UploadedFile::fake()->create('logo.png', 10, 'image/png');

        $response = $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), [
            'school_name' => 'Sample State University',
            'school_short_name' => 'SSU',
            'school_tagline' => 'Custom Tagline',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        Branding::forget();
        $brand = Branding::get();

        $this->assertSame('Sample State University', $brand['school_name']);
        $this->assertSame('SSU', $brand['school_short_name']);
        $this->assertNotNull($brand['logo_url']);
        Storage::disk('public')->assertExists(Setting::getValue('school_logo_path'));
    }
}
