<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['name', 'max_votes'];

    // SECURITY: election_id is deliberately left out of $fillable. A
    // position must always belong to whichever election the admin is
    // currently managing (set server-side in Admin\PositionController via
    // forceFill()), never trusted from form input — otherwise a crafted
    // request could attach a position to an arbitrary/closed election.
    protected $guarded = ['election_id'];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
