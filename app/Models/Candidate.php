<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Candidate extends Model
{
    protected $fillable = ['name', 'party_list', 'position_id', 'image'];

    // SECURITY: same reasoning as Position::$guarded — election_id is set
    // server-side from the admin's currently-managed election, never from
    // request input.
    protected $guarded = ['election_id'];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * BUG FIX: this used to always return a URL — including
     * asset('images/default-avatar.png') when there was no photo, but that
     * file never existed in the repo. Since the accessor never returned an
     * empty value, `@if($candidate->image_url)` in the dashboard/ballot
     * views was always truthy, so the "no photo" initials-circle fallback
     * those views were written for could never actually render; voters saw
     * a broken image icon instead. Returning null when there's no photo
     * lets that existing fallback UI work as designed — no new asset file
     * needed.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? Storage::disk('public')->url('candidates/' . $this->image)
            : null;
    }
}
