<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ballot extends Model
{
    // FIX: the ballots table (see migration) only has voted_at, id,
    // voter_id, election_id — no created_at/updated_at columns. Eloquent
    // defaults to managing both timestamps unless told otherwise; only
    // UPDATED_AT was nulled out before, so every insert tried (and failed)
    // to write a created_at column that doesn't exist. voted_at already is
    // this row's creation timestamp, so there's nothing for created_at to
    // add — null it out too instead of adding a redundant column.
    const CREATED_AT = null;

    const UPDATED_AT = null;

    // SECURITY: a Ballot row is proof "this voter has voted in this
    // election" — same sensitivity as the old has_voted flag. Never
    // mass-assignable from request input; only ever written server-side
    // inside VoterDashboardController::submitVote()'s locked transaction,
    // using forceFill()->save(). See Voter::$fillable for the same pattern.
    protected $fillable = [];

    protected $guarded = ['voter_id', 'election_id', 'voted_at', 'receipt_code', 'commitment'];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public static function hasVoted(int $voterId, int $electionId): bool
    {
        return static::where('voter_id', $voterId)
            ->where('election_id', $electionId)
            ->exists();
    }

    /**
     * A short, human-friendly, collision-checked code the voter can screenshot
     * or write down as proof they voted. It is NOT tied to any candidate
     * choice — it only ever gets attached to a Ballot row (participation),
     * never to a Vote row (choice), so ballot secrecy is unaffected.
     */
    public static function generateReceiptCode(): string
    {
        do {
            $code = 'JRMSU-' . strtoupper(Str::random(8));
        } while (static::where('receipt_code', $code)->exists());

        return $code;
    }

    /**
     * Turn a submitted `votes[position_id][] = candidate_id` payload into a
     * single deterministic string: same selections always produce the same
     * string, regardless of array key order, JSON key order, or whether a
     * single-select position sent its candidate as an array or a bare
     * value. This determinism is what lets a voter re-derive their own
     * commitment later (see computeCommitment()) purely from re-entering
     * the same choices — no server-side storage of the plaintext ballot is
     * needed.
     *
     * @param array<int|string, mixed> $submittedVotes
     */
    public static function canonicalizeVotes(array $submittedVotes): string
    {
        $normalized = collect($submittedVotes)
            ->map(function ($candidateData, $positionId) {
                $candidates = is_array($candidateData) ? $candidateData : [$candidateData];

                return [
                    'position_id' => (int) $positionId,
                    'candidate_ids' => collect($candidates)
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('position_id')
            ->values()
            ->all();

        return json_encode($normalized);
    }

    /**
     * The cryptographic commitment stored on a Ballot row: a SHA-256 hash
     * that binds together the election, the voter's own receipt code (which
     * only the voter and this row know), and their canonical choices.
     *
     * Anyone can see this hash (it's public, part of the Merkle tree — see
     * MerkleTree / Election::merkle_root). It reveals nothing on its own:
     * finding the two inputs that hash to it requires already knowing the
     * receipt code, which only the voter who cast this ballot has. That's
     * what lets a voter later prove "this hash is mine and it committed to
     * these exact choices" on the /verify page, without anyone else being
     * able to do the same.
     */
    public static function computeCommitment(int $electionId, string $receiptCode, string $canonicalVotes): string
    {
        return hash('sha256', $electionId . '|' . $receiptCode . '|' . $canonicalVotes);
    }
}
