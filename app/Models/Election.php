<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    // SECURITY: 'status' is intentionally left out of $fillable. It gates
    // whether an election is live and votable, so it must never be settable
    // from raw request input on a generic create/update — only through the
    // dedicated, audited open()/close() actions in Admin\ElectionController,
    // which use forceFill()->save() (same guard pattern as Voter::has_voted
    // and Voter::is_approved).
    protected $fillable = ['title', 'start_time', 'end_time'];

    // SECURITY: merkle_root/merkle_leaf_count/results_locked_at are the
    // frozen, publicly-verifiable "certification" of this election's final
    // ballot set (see MerkleTree + database/migrations/..._add_verification
    // _fields.php). Same guard pattern as 'status' above — only ever
    // written by Admin\ElectionController::close() via forceFill(), never
    // from request input.
    protected $guarded = ['status', 'merkle_root', 'merkle_leaf_count', 'results_locked_at'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'results_locked_at' => 'datetime',
    ];

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Closed elections become read-only history in the admin panel — their
     * positions/candidates can be viewed but not created, edited, or deleted.
     */
    public function isEditable(): bool
    {
        return $this->status !== 'closed';
    }

    /**
     * Whether this election's ballot set has been certified: its Merkle
     * root was computed and frozen at close time. Before this, /verify can
     * still confirm a ballot's commitment is part of the live (not yet
     * final) set — see VerifyController.
     */
    public function isResultsLocked(): bool
    {
        return $this->results_locked_at !== null && $this->merkle_root !== null;
    }

    /**
     * The single election voters can currently see and vote in, if any.
     * Only one election can hold status='open' at a time (enforced in
     * Admin\ElectionController::open()).
     */
    public static function openOne(): ?self
    {
        return static::where('status', 'open')->first();
    }

    /**
     * Whether right now falls inside this election's configured voting
     * window. A missing start/end time means the schedule hasn't been set,
     * which is treated as "not open" rather than "always open".
     */
    public function isWithinVotingWindow(): bool
    {
        if (! $this->start_time || ! $this->end_time) {
            return false;
        }

        $now = now();

        return $now->greaterThanOrEqualTo($this->start_time) && $now->lessThanOrEqualTo($this->end_time);
    }
}
