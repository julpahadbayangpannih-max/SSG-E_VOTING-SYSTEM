<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Ballot;
use App\Models\Election;
use App\Models\Vote;
use App\Services\MerkleTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ElectionController extends Controller
{
    use LogsActivity, ResolvesCurrentElection;

    private function admin()
    {
        return Auth::guard('admin')->user();
    }

    public function index()
    {
        $elections = Election::withCount(['positions', 'candidates', 'votes'])
            ->orderByDesc('id')
            ->get();

        $current = $this->currentElection();

        return view('admin.elections', compact('elections', 'current'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
        ]);

        // status is guarded — every new election starts in 'draft' via the
        // column default, never settable from this form.
        $election = Election::create($data);

        // New election immediately becomes what the admin is managing, so
        // Positions/Candidates pages "just work" right after creating it.
        session(['admin_current_election_id' => $election->id]);

        $admin = $this->admin();
        $this->auditLog($request, 'election_created', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'title' => $election->title,
        ]);

        return response()->json(['success' => true, 'id' => $election->id]);
    }

    public function update(Request $request, Election $election)
    {
        if (! $election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed and its details can no longer be edited.'], 422);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
        ]);

        $old = $election->only(['title', 'start_time', 'end_time']);
        $election->update($data);

        $admin = $this->admin();
        $this->auditLog($request, 'election_updated', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'before' => $old,
            'after' => $data,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Make this the election voters currently see and can cast ballots in.
     * Kept simple on purpose (per project requirements): only one election
     * may be open at a time, so this refuses if another one already is —
     * the admin must close it first rather than this silently closing it
     * for them.
     */
    public function open(Request $request, Election $election)
    {
        if ($election->isClosed()) {
            return response()->json(['success' => false, 'message' => 'A closed election cannot be reopened.'], 422);
        }

        $alreadyOpen = Election::where('status', 'open')->where('id', '!=', $election->id)->first();
        if ($alreadyOpen) {
            return response()->json([
                'success' => false,
                'message' => "\"{$alreadyOpen->title}\" is currently open. Close it first before opening another election.",
            ], 422);
        }

        // Same guard pattern as Voter::has_voted / Voter::is_approved —
        // 'status' isn't fillable, so this trusted write uses forceFill().
        $election->forceFill(['status' => 'open'])->save();

        $admin = $this->admin();
        $this->auditLog($request, 'election_opened', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'title' => $election->title,
        ]);

        return response()->json(['success' => true]);
    }

    public function close(Request $request, Election $election)
    {
        if (! $election->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Only the currently open election can be closed.'], 422);
        }

        // CERTIFICATION: freeze the Merkle root over every ballot commitment
        // cast in this election, at the moment it closes. From this point
        // on, /verify (and `php artisan system:integrity-check`) can detect
        // any addition, removal, or edit to the ballots table after
        // certification by simply recomputing the root and comparing it —
        // it will only ever match if the exact same set of commitments is
        // still present. Ballots cast before this feature shipped (no
        // commitment yet) are excluded from the tree rather than treated as
        // tampering.
        $commitments = Ballot::where('election_id', $election->id)
            ->whereNotNull('commitment')
            ->pluck('commitment')
            ->all();
        $merkleRoot = MerkleTree::buildRoot($commitments);

        $election->forceFill([
            'status' => 'closed',
            'merkle_root' => $merkleRoot,
            'merkle_leaf_count' => count($commitments),
            'results_locked_at' => now(),
        ])->save();

        $admin = $this->admin();
        $this->auditLog($request, 'election_closed', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'title' => $election->title,
            'merkle_root' => $merkleRoot,
            'merkle_leaf_count' => count($commitments),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Switch which election the admin is currently managing on the
     * Positions/Candidates pages. Does not affect what voters see — that's
     * controlled entirely by which election has status='open'.
     */
    public function switch(Request $request, Election $election)
    {
        session(['admin_current_election_id' => $election->id]);

        $admin = $this->admin();
        $this->auditLog($request, 'election_switched', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'title' => $election->title,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Election $election)
    {
        // Same protection pattern as PositionController/CandidateController
        // ::destroy() — never let cast votes disappear silently.
        $voteCount = Vote::where('election_id', $election->id)->count();
        if ($voteCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete this election: {$voteCount} vote(s) have already been cast in it.",
            ], 422);
        }

        $admin = $this->admin();
        $this->auditLog(request(), 'election_deleted', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'title' => $election->title,
        ]);

        if (session('admin_current_election_id') === $election->id) {
            session()->forget('admin_current_election_id');
        }

        $election->delete();

        return response()->json(['success' => true]);
    }
}
