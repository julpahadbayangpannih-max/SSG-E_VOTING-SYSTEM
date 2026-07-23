<?php

namespace App\Http\Controllers\Voter;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoterDashboardController extends Controller
{
    use LogsActivity;

    /**
     * The one election voters can currently interact with, plus whether it's
     * actually votable right now. status='open' is the admin's on/off
     * switch; start_time/end_time is the schedule within that. Both must
     * agree for voting to be allowed.
     */
    private function electionStatus(): array
    {
        // FIX: this used to call date_default_timezone_set('Asia/Manila')
        // here. Two real problems with that:
        //   1. It's a global, process-wide PHP setting — on a PHP-FPM/
        //      Octane worker that handles more than one request, it could
        //      leak into *other concurrent requests* being served by the
        //      same process, silently changing their date/time behavior.
        //   2. app.timezone is 'UTC', and start_time/end_time are stored
        //      and cast as UTC (see Election::$casts). Shifting only
        //      now() to Asia/Manila (UTC+8) while comparing it against
        //      UTC-stored bounds put voting windows off by 8 hours —
        //      opening or closing elections 8 hours early/late depending
        //      on direction.
        // now() below simply uses the app's configured timezone (UTC),
        // consistent with how start_time/end_time are stored. If a Manila-
        // local *display* is ever wanted, convert at the view layer with
        // ->timezone('Asia/Manila') on a specific Carbon instance — never
        // by mutating global PHP state mid-request.
        $election = Election::openOne();

        if (! $election) {
            return ['open' => false, 'election' => null, 'message' => 'There is no election open for voting right now.'];
        }

        if (! $election->start_time || ! $election->end_time) {
            return ['open' => false, 'election' => $election, 'message' => 'The election schedule has not been set by the administrator yet.'];
        }

        $now = now();

        if ($now->lessThan($election->start_time)) {
            return ['open' => false, 'election' => $election, 'message' => 'Voting has not started yet. The election will begin on ' . $election->start_time->format('F j, Y \a\t g:i A') . '.'];
        }

        if ($now->greaterThan($election->end_time)) {
            return ['open' => false, 'election' => $election, 'message' => 'Voting is officially closed. The election ended on ' . $election->end_time->format('F j, Y \a\t g:i A') . '.'];
        }

        return ['open' => true, 'election' => $election, 'message' => ''];
    }

    public function index()
    {
        /** @var Voter $voter */
        $voter = Auth::guard('voter')->user();
        $status = $this->electionStatus();
        $election = $status['election'];

        $positions = $election
            ? Position::with(['candidates' => fn ($q) => $q->orderBy('name')])
                ->where('election_id', $election->id)->orderBy('name')->get()
            : collect();

        $resultsRaw = $election ? $this->getResults($election->id) : [];
        $alreadyVoted = $election ? $voter->hasVotedIn($election->id) : false;
        $receiptCode = $alreadyVoted
            ? Ballot::where('voter_id', $voter->id)->where('election_id', $election->id)->value('receipt_code')
            : null;

        return view('voter.dashboard', compact('voter', 'status', 'positions', 'resultsRaw', 'alreadyVoted', 'receiptCode'));
    }

    public function submitVote(Request $request)
    {
        /** @var Voter $voter */
        $voter = Auth::guard('voter')->user();
        $status = $this->electionStatus();
        $election = $status['election'];

        if (! $status['open']) {
            $this->auditLog($request, 'vote_attempt_outside_window', 'voter', $voter->id, $voter->name);
            abort(403, 'Voting is currently closed.');
        }

        // NOTE: this initial check is just a fast, user-friendly rejection for
        // the common case. It is NOT the real protection against double voting —
        // see the locked re-check inside DB::transaction() below, which is what
        // actually closes the race condition.
        if ($voter->hasVotedIn($election->id)) {
            $this->auditLog($request, 'vote_attempt_double_vote', 'voter', $voter->id, $voter->name, [
                'election_id' => $election->id,
            ]);
            abort(403, 'You have already submitted your ballot for this election.');
        }

        $submittedVotes = $request->input('votes', []);
        // Scoped to this election — a position id from a different election
        // (past or future) is simply not in this list, so it gets rejected
        // below instead of silently falling back to a default max of 1.
        $positionLimits = Position::where('election_id', $election->id)->pluck('max_votes', 'id');

        foreach ($submittedVotes as $positionId => $candidateData) {
            if (! $positionLimits->has($positionId)) {
                $this->auditLog($request, 'vote_invalid_position', 'voter', $voter->id, $voter->name, [
                    'position_id' => $positionId,
                    'election_id' => $election->id,
                ]);
                abort(422, "Position #{$positionId} does not belong to the current election. Ballot is void.");
            }

            // FIX #3: always treat as array before counting (prevents overvote bypass)
            $candidates = is_array($candidateData) ? $candidateData : [$candidateData];
            $maxAllowed = $positionLimits[$positionId];
            $selected = count($candidates);

            if ($selected > $maxAllowed) {
                $this->auditLog($request, 'vote_overvote_detected', 'voter', $voter->id, $voter->name, [
                    'position_id' => $positionId,
                    'selected' => $selected,
                    'max_allowed' => $maxAllowed,
                ]);
                abort(422, "Overvoting detected for position #{$positionId}. Ballot is void.");
            }

            // FIX #2: validate each candidate actually belongs to the claimed position
            foreach ($candidates as $candidateId) {
                $valid = Candidate::where('id', $candidateId)
                    ->where('position_id', $positionId)
                    ->exists();
                if (! $valid) {
                    $this->auditLog($request, 'vote_invalid_candidate', 'voter', $voter->id, $voter->name, [
                        'position_id' => $positionId,
                        'candidate_id' => $candidateId,
                    ]);
                    abort(422, "Invalid candidate #{$candidateId} for position #{$positionId}. Ballot is void.");
                }
            }
        }

        $alreadyVoted = false;
        $receiptCode = null;

        DB::transaction(function () use ($voter, $election, $submittedVotes, &$alreadyVoted, &$receiptCode) {
            // FIX #4 (race condition): lock the voter row inside the
            // transaction. lockForUpdate() makes any other transaction trying
            // to touch this same voter row WAIT until this one commits or
            // rolls back — this is what actually makes "check ballot exists,
            // then write" atomic for two simultaneous submitVote() calls by
            // the same voter, same pattern as before, just re-checking the
            // Ballot table (per-election) instead of a global has_voted flag.
            $lockedVoter = Voter::where('id', $voter->id)->lockForUpdate()->first();

            if (Ballot::hasVoted($lockedVoter->id, $election->id)) {
                $alreadyVoted = true;

                return; // exits the transaction closure; nothing gets written
            }

            foreach ($submittedVotes as $positionId => $candidateData) {
                $candidates = is_array($candidateData) ? $candidateData : [$candidateData];
                foreach ($candidates as $candidateId) {
                    // BALLOT SECRECY: no voter_id here on purpose. A Vote row
                    // only ever records which election/position/candidate it
                    // belongs to — never who cast it. Proof that *this* voter
                    // voted lives solely in the Ballot row written below.
                    $vote = new Vote([
                        'position_id' => $positionId,
                        'candidate_id' => $candidateId,
                    ]);
                    // election_id is guarded — set server-side only.
                    $vote->forceFill(['election_id' => $election->id])->save();
                }
            }

            // The ballot row is what actually marks "voted in this election"
            // now (replaces the old Voter::has_voted flag). Guarded fields,
            // so written via forceFill() — same trusted-server-write pattern
            // used throughout this codebase. The unique(voter_id, election_id)
            // DB constraint is the second layer of protection behind the lock.
            $receiptCode = Ballot::generateReceiptCode();

            // END-TO-END VERIFIABILITY: bind this ballot to a public
            // commitment hash derived from the election, this ballot's own
            // (secret-until-now) receipt code, and the voter's exact
            // choices — see Ballot::computeCommitment(). Only this request
            // ever has all three inputs together; from here on, recreating
            // the commitment requires the receipt code, which only the
            // voter holds. This is what /verify checks against the
            // published Merkle root, without ever storing or exposing which
            // candidates were chosen.
            $canonicalVotes = Ballot::canonicalizeVotes($submittedVotes);
            $commitment = Ballot::computeCommitment($election->id, $receiptCode, $canonicalVotes);

            (new Ballot)->forceFill([
                'voter_id' => $lockedVoter->id,
                'election_id' => $election->id,
                'voted_at' => now(),
                'receipt_code' => $receiptCode,
                'commitment' => $commitment,
            ])->save();
        });

        if ($alreadyVoted) {
            $this->auditLog($request, 'vote_attempt_double_vote_race', 'voter', $voter->id, $voter->name, [
                'election_id' => $election->id,
            ]);
            abort(403, 'You have already submitted your ballot for this election.');
        }

        // BALLOT SECRECY: this entry records that $voter cast a ballot
        // (turnout, timing, position count) but deliberately does NOT
        // include $voteSummary — logging candidate choices next to the
        // voter's identity would recreate the exact traceability problem
        // that dropping votes.voter_id from the schema was meant to close.
        // Per-candidate tallies are already public via getResults()/
        // liveResults(), so nothing is lost by leaving choices out here.
        $this->auditLog($request, 'vote_submitted', 'voter', $voter->id, $voter->name, [
            'election_id' => $election->id,
            'positions_voted' => count($submittedVotes),
        ]);

        return redirect()->route('voter.dashboard')->with('voted', true)->with('receipt_code', $receiptCode);
    }

    /**
     * FIX #1: Public JSON endpoint for live results — accessible by voter dashboard JS.
     * No auth required; results are already public after election closes or voter has voted.
     */
    public function liveResults()
    {
        $election = Election::openOne();

        return response()->json(['success' => true, 'data' => $election ? $this->getResults($election->id) : []]);
    }

    private function getResults(int $electionId): array
    {
        return Candidate::with('position')
            ->where('candidates.election_id', $electionId)
            ->select([
                'candidates.*',
                DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.candidate_id = candidates.id AND votes.election_id = ' . (int) $electionId . ') as vote_count'),
            ])
            ->get()
            ->map(fn ($c) => [
                'candidateId' => $c->id,
                'candidateName' => $c->name,
                'partyList' => $c->party_list,
                'image' => $c->image_url,
                'positionId' => $c->position_id,
                'positionName' => $c->position->name ?? '',
                'maxVotes' => $c->position->max_votes ?? 1,
                'voteCount' => (int) $c->vote_count,
            ])
            ->sortByDesc('voteCount')
            ->values()
            ->toArray();
    }
}
