<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Ballot;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VoterController extends Controller
{
    use LogsActivity, ResolvesCurrentElection;

    private function admin()
    {
        return Auth::guard('admin')->user();
    }

    public function index()
    {
        $voters = Voter::orderBy('name')->get();
        $election = $this->currentElection();
        $votedIds = $election
            ? Ballot::where('election_id', $election->id)->pluck('voter_id')->all()
            : [];

        return view('admin.voters', compact('voters', 'election', 'votedIds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'unique:voters,student_id'],
            'name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
        ]);

        $tempPassword = Str::password(10, symbols: false);

        // NOTE: Voter::create([...'is_approved' => true...]) would silently
        // drop 'is_approved' — it's deliberately excluded from Voter::$fillable,
        // so every admin-added voter ended up unapproved despite this call
        // looking like it approved them. forceFill() bypasses that guard for
        // this trusted, server-controlled write.
        $voter = new Voter($data);
        $voter->forceFill([
            'is_approved' => true,
            'password' => Hash::make($tempPassword),
        ])->save();

        $admin = $this->admin();
        $this->auditLog($request, 'voter_added', 'admin', $admin->id, $admin->name, [
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'student_id' => $voter->student_id,
        ]);

        // FIX: temp password is shown ONCE here, never stored in plaintext or
        // logged. Admin must relay it to the student through an official channel.
        return response()->json([
            'success' => true,
            'message' => 'Voter added successfully.',
            'temp_password' => $tempPassword,
        ]);
    }

    public function update(Request $request, Voter $voter)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'unique:voters,student_id,' . $voter->id],
            'name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
        ]);

        $old = $voter->only(['student_id', 'name', 'course']);
        $voter->update($data);

        $admin = $this->admin();
        $this->auditLog($request, 'voter_updated', 'admin', $admin->id, $admin->name, [
            'voter_id' => $voter->id,
            'before' => $old,
            'after' => $data,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Voter $voter)
    {
        $request = request();
        $admin = $this->admin();
        $this->auditLog($request, 'voter_deleted', 'admin', $admin->id, $admin->name, [
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'student_id' => $voter->student_id,
        ]);

        $voter->delete();

        return response()->json(['success' => true]);
    }

    public function approve(Voter $voter)
    {
        $tempPassword = Str::password(10, symbols: false);

        // Same guard issue as store() above — forceFill() so is_approved
        // actually persists instead of being silently dropped.
        $voter->forceFill([
            'is_approved' => true,
            'password' => Hash::make($tempPassword),
        ])->save();

        $admin = $this->admin();
        $this->auditLog(request(), 'voter_approved', 'admin', $admin->id, $admin->name, [
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'student_id' => $voter->student_id,
        ]);

        // FIX (account takeover): previously, approved voters had a null
        // password and whoever logged in first with their student_id claimed
        // the account permanently. Now the admin generates the temp password
        // here and relays it to the actual student — nobody can "race" to
        // claim an account anymore.
        return response()->json([
            'success' => true,
            'temp_password' => $tempPassword,
        ]);
    }

    public function reject(Voter $voter)
    {
        $admin = $this->admin();
        $this->auditLog(request(), 'voter_rejected', 'admin', $admin->id, $admin->name, [
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'student_id' => $voter->student_id,
        ]);

        $voter->delete();

        return response()->json(['success' => true, 'message' => 'Registration rejected and removed.']);
    }

    /**
     * Bulk-create approved voters from an uploaded CSV (columns: student_id,
     * name, course — header row required, any column order). Rows with a
     * student_id that already exists are skipped, not overwritten — this is
     * an "add new voters" tool, not a sync/update tool, so it can't silently
     * clobber an existing voter's approval state or password.
     *
     * Every created voter gets its own temp password, same as store()/
     * approve() above, returned once in the response so the admin can
     * distribute them. Nothing is emailed or persisted in plaintext.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return response()->json(['success' => false, 'message' => 'The CSV file is empty.'], 422);
        }

        // Normalize header cells: trim, lowercase, strip a UTF-8 BOM if present.
        $header = array_map(
            fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))),
            $header
        );

        $required = ['student_id', 'name', 'course'];
        if (array_diff($required, $header)) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'CSV header row must include: student_id, name, course.',
            ], 422);
        }

        $created = [];
        $skipped = [];
        $rowNum = 1; // header was row 1

        DB::transaction(function () use ($handle, $header, &$created, &$skipped, &$rowNum) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue; // silently skip blank lines
                }

                $data = array_combine($header, array_pad($row, count($header), null));

                $studentId = trim((string) ($data['student_id'] ?? ''));
                $name = trim((string) ($data['name'] ?? ''));
                $course = trim((string) ($data['course'] ?? ''));

                if ($studentId === '' || $name === '' || $course === '') {
                    $skipped[] = ['row' => $rowNum, 'student_id' => $studentId, 'reason' => 'Missing required field(s).'];

                    continue;
                }

                if (Voter::where('student_id', $studentId)->exists()) {
                    $skipped[] = ['row' => $rowNum, 'student_id' => $studentId, 'reason' => 'Student ID already exists.'];

                    continue;
                }

                $tempPassword = Str::password(10, symbols: false);

                // Same forceFill() pattern as store()/approve(): is_approved
                // is guarded, so a plain create() would silently leave the
                // voter unapproved.
                $voter = new Voter(['student_id' => $studentId, 'name' => $name, 'course' => $course]);
                $voter->forceFill([
                    'is_approved' => true,
                    'password' => Hash::make($tempPassword),
                ])->save();

                $created[] = [
                    'student_id' => $studentId,
                    'name' => $name,
                    'course' => $course,
                    'temp_password' => $tempPassword,
                ];
            }
        });

        fclose($handle);

        $admin = $this->admin();
        $this->auditLog($request, 'voters_bulk_imported', 'admin', $admin->id, $admin->name, [
            'created_count' => count($created),
            'skipped_count' => count($skipped),
        ]);

        return response()->json([
            'success' => true,
            'message' => count($created) . ' voter(s) imported, ' . count($skipped) . ' skipped.',
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }
}
