<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    // Only track created_at; skip updated_at (votes are immutable)
    const UPDATED_AT = null;

    protected $fillable = ['position_id', 'candidate_id'];

    // SECURITY: election_id is never mass-assignable. It's set server-side
    // in VoterDashboardController::submitVote() from the currently-open
    // election (forceFill()->save()), never from the submitted ballot —
    // a voter's request never says which election it's for.
    protected $guarded = ['election_id'];

    // BALLOT SECRECY: a vote intentionally has no voter_id column and no
    // relation back to Voter. "Who has voted" is answered by the separate
    // Ballot model instead (see Ballot::hasVoted()), which knows *that* a
    // voter voted but not *what* they chose. Do not add a link from this
    // model back to the voter who cast it — that would recreate a way to
    // trace an individual's ballot.
    protected $casts = ['created_at' => 'datetime'];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
