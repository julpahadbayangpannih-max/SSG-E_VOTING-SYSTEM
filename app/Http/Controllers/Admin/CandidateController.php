<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CandidateController extends Controller
{
    use LogsActivity, ResolvesCurrentElection;

    private function admin()
    {
        return Auth::guard('admin')->user();
    }

    public function index()
    {
        $election = $this->currentElection();

        $candidates = $election
            ? Candidate::with('position')->where('election_id', $election->id)->orderBy('name')->get()
            : collect();
        $positions = $election
            ? Position::where('election_id', $election->id)->orderBy('name')->get()
            : collect();

        return view('admin.candidates', compact('candidates', 'positions', 'election'));
    }

    public function store(Request $request)
    {
        $election = $this->currentElection();

        if (! $election) {
            return response()->json(['success' => false, 'message' => 'Create an election first before adding candidates.'], 422);
        }
        if (! $election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Candidates are read-only.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'party_list' => ['nullable', 'string', 'max:255'],
            // A position id alone isn't enough — it must also belong to the
            // election currently being managed, or an admin could attach a
            // candidate to a position from a different (possibly closed)
            // election just by knowing its id.
            'position_id' => ['required', Rule::exists('positions', 'id')->where('election_id', $election->id)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Stored via the `public` disk (storage/app/public, symlinked to
            // public/storage) rather than public_path() directly — same
            // pattern already used for branding logos in SettingController.
            // This is what makes an uploaded photo actually survive a
            // redeploy on a host with a persistent disk mounted at
            // storage/app (see render.yaml); public_path() writes land on
            // the container's ephemeral filesystem and are wiped on every
            // deploy/restart there.
            $filename = Str::random(20) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('candidates', $filename, 'public');
            $data['image'] = $filename;
        }

        $candidate = new Candidate($data);
        $candidate->forceFill(['election_id' => $election->id])->save();
        $position = Position::find($data['position_id']);

        $admin = $this->admin();
        $this->auditLog($request, 'candidate_added', 'admin', $admin->id, $admin->name, [
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->name,
            'position' => $position->name ?? '',
            'election_id' => $election->id,
        ]);

        return response()->json(['success' => true, 'id' => $candidate->id]);
    }

    public function update(Request $request, Candidate $candidate)
    {
        if (! $candidate->election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Candidates are read-only.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'party_list' => ['nullable', 'string', 'max:255'],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('election_id', $candidate->election_id)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($candidate->image) {
                Storage::disk('public')->delete('candidates/' . $candidate->image);
            }
            $filename = Str::random(20) . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('candidates', $filename, 'public');
            $data['image'] = $filename;
        }

        $old = $candidate->only(['name', 'party_list', 'position_id']);
        $candidate->update($data);

        $admin = $this->admin();
        $this->auditLog($request, 'candidate_updated', 'admin', $admin->id, $admin->name, [
            'candidate_id' => $candidate->id,
            'before' => $old,
            'after' => Arr::except($data, ['image']),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Candidate $candidate)
    {
        if (! $candidate->election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Candidates are read-only.'], 422);
        }

        // FIX: don't let a candidate be deleted if votes already exist for
        // them — same reasoning as PositionController::destroy().
        $voteCount = $candidate->votes()->count();
        if ($voteCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete this candidate: {$voteCount} vote(s) have already been cast for them. Use \"Reset Votes\" in Results first if you really want to remove them.",
            ], 422);
        }

        $admin = $this->admin();
        $this->auditLog(request(), 'candidate_deleted', 'admin', $admin->id, $admin->name, [
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->name,
        ]);

        if ($candidate->image) {
            Storage::disk('public')->delete('candidates/' . $candidate->image);
        }
        $candidate->delete();

        return response()->json(['success' => true]);
    }
}
