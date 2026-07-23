<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Voter extends Authenticatable
{
    // SECURITY: is_approved is intentionally left out of $fillable. It gates
    // "am I allowed to vote", so it must never be settable from raw request
    // input — e.g. Voter::create($request->all()) or
    // $voter->fill($request->all()) must NOT be able to let a voter
    // self-approve.
    //
    // "Have I already voted" no longer lives on this model at all — it's
    // per-election now, answered by whether a Ballot row exists for
    // (voter_id, election_id). See Ballot::hasVoted().
    //
    // NOTE: when $fillable is a non-empty array (as it is here), Eloquent
    // ignores $guarded entirely for mass-assignment purposes — a key not
    // listed in $fillable is blocked regardless of $guarded. So $guarded
    // below has no real effect; the protection comes purely from omission
    // above. It's kept only as an explicit, readable reminder of intent.
    //
    // Because of that same rule, trusted internal code that legitimately
    // needs to write this field (Admin\VoterController::store()/approve())
    // CANNOT use ->update()/->create() with this key — that silently
    // no-ops. It must use forceFill() on a model instance, or a
    // query-builder ::update() (e.g. Voter::where(...)->update([...])),
    // both of which bypass the mass-assignment guard intentionally.
    protected $fillable = [
        'student_id', 'name', 'course', 'password',
    ];

    protected $guarded = ['is_approved'];

    protected $hidden = ['password'];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // BALLOT SECRECY: intentionally no votes() relation here. Vote rows no
    // longer carry a voter_id, so there is no query path from a Voter back
    // to the candidates they chose. Ballot is the only per-voter record,
    // and it only proves participation, not choice.

    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    public function hasVotedIn(int $electionId): bool
    {
        return Ballot::hasVoted($this->id, $electionId);
    }
}
