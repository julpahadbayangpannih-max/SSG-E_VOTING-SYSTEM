<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use ResolvesCurrentElection;

    public function index()
    {
        $election = $this->currentElection();

        $positions = $election
            ? Position::where('election_id', $election->id)->orderBy('name')->get()
            : collect();

        return view('admin.positions', compact('positions', 'election'));
    }

    public function store(Request $request)
    {
        $election = $this->currentElection();

        if (! $election) {
            return response()->json(['success' => false, 'message' => 'Create an election first before adding positions.'], 422);
        }
        if (! $election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Positions are read-only.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_votes' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $position = new Position($data);
        // election_id is guarded — set server-side only, never from request input.
        $position->forceFill(['election_id' => $election->id])->save();

        return response()->json(['success' => true, 'id' => $position->id]);
    }

    public function update(Request $request, Position $position)
    {
        if (! $position->election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Positions are read-only.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_votes' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $position->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(Position $position)
    {
        if (! $position->election->isEditable()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Positions are read-only.'], 422);
        }

        // FIX: don't let a position be deleted if votes already exist for it —
        // the DB cascadeOnDelete would silently wipe those votes with no
        // specific record of what was lost. Admin must reset votes first if
        // they truly want to remove a position that already has ballots cast.
        $voteCount = $position->votes()->count();
        if ($voteCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete this position: {$voteCount} vote(s) have already been cast for it. Use \"Reset Votes\" in Results first if you really want to remove it.",
            ], 422);
        }

        $position->delete();

        return response()->json(['success' => true]);
    }
}
