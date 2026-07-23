<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogsActivity;
use App\Models\Ballot;
use App\Services\MerkleTree;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PUBLIC BALLOT VERIFICATION ("Verify My Vote").
 *
 * Anyone — with just their own receipt code, no login required — can
 * confirm that:
 *   1. a ballot was in fact recorded for that receipt code, and
 *   2. that ballot's commitment is part of the exact set of ballots the
 *      election was tallied from (once results are certified, that set is
 *      frozen and any later change to it is detectable).
 *
 * It never asks for or reveals which candidates were chosen. See
 * app/Models/Ballot.php (computeCommitment/canonicalizeVotes) and
 * app/Services/MerkleTree.php for how the underlying commitment/proof
 * scheme works, and Admin\ElectionController::close() for where the
 * published root gets frozen.
 */
class VerifyController extends Controller
{
    use LogsActivity;

    public function show(Request $request)
    {
        return view('verify.index', [
            'result' => null,
            'submittedCode' => $request->query('receipt_code'),
        ]);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'receipt_code' => ['required', 'string', 'max:40'],
        ]);

        // Receipt codes are always generated + stored as JRMSU-XXXXXXXX
        // (uppercase). Normalize input the same way so pasted-with-stray-
        // spaces or lowercased codes still match.
        $code = strtoupper(trim($data['receipt_code']));

        $ballot = Ballot::with('election')->where('receipt_code', $code)->first();

        // Masked for logging: enough to spot a specific voter contacting
        // support about a specific attempt, not enough for a log reader to
        // reconstruct a working receipt code from it.
        $maskedCode = Str::limit($code, 10, '…');

        if (! $ballot) {
            $this->auditLog($request, 'ballot_verification_not_found', 'public', null, 'anonymous', [
                'receipt_code' => $maskedCode,
            ]);

            return view('verify.index', [
                'result' => ['status' => 'not_found'],
                'submittedCode' => $code,
            ]);
        }

        $election = $ballot->election;

        if (! $ballot->commitment) {
            // Cast before this feature existed — nothing to build a proof
            // from. Still a true, honest answer: we can confirm the ballot
            // row exists, just not cryptographically.
            $this->auditLog($request, 'ballot_verification_legacy', 'public', null, 'anonymous', [
                'receipt_code' => $maskedCode,
                'election_id' => $election?->id,
            ]);

            return view('verify.index', [
                'result' => [
                    'status' => 'legacy',
                    'election_title' => $election?->title,
                    'voted_at' => $ballot->voted_at,
                ],
                'submittedCode' => $code,
            ]);
        }

        $commitments = Ballot::where('election_id', $election->id)
            ->whereNotNull('commitment')
            ->pluck('commitment')
            ->all();

        $liveRoot = MerkleTree::buildRoot($commitments);
        $proof = MerkleTree::buildProof($commitments, $ballot->commitment);
        $includedInLiveSet = $proof && $liveRoot && MerkleTree::verifyProof($ballot->commitment, $proof, $liveRoot);

        if ($election->isResultsLocked()) {
            $rootMatches = $liveRoot && hash_equals($election->merkle_root, $liveRoot);

            $status = match (true) {
                $rootMatches && $includedInLiveSet => 'certified',
                ! $rootMatches => 'integrity_alert',
                default => 'error',
            };
        } else {
            $status = $includedInLiveSet ? 'provisional' : 'error';
        }

        $this->auditLog($request, 'ballot_verification_checked', 'public', null, 'anonymous', [
            'receipt_code' => $maskedCode,
            'election_id' => $election->id,
            'status' => $status,
        ]);

        return view('verify.index', [
            'result' => [
                'status' => $status,
                'election_title' => $election->title,
                'voted_at' => $ballot->voted_at,
                'certified_at' => $election->results_locked_at,
                'merkle_root' => $election->isResultsLocked() ? $election->merkle_root : $liveRoot,
                'leaf_count' => $election->isResultsLocked() ? $election->merkle_leaf_count : count($commitments),
                'commitment' => $ballot->commitment,
            ],
            'submittedCode' => $code,
        ]);
    }
}
