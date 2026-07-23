<?php

namespace App\Http\Traits;

use App\Models\Election;

trait ResolvesCurrentElection
{
    /**
     * The election the admin is currently managing (Positions/Candidates
     * pages act on this one). Chosen via the switcher in
     * Admin\ElectionController::switch(), remembered in the session.
     *
     * Falls back sensibly if nothing is selected yet, or if the previously
     * selected election no longer exists: prefer the open election (the
     * one actually being voted in), otherwise the most recently created
     * election. Returns null only when no elections exist at all.
     */
    protected function currentElection(): ?Election
    {
        $selectedId = session('admin_current_election_id');

        if ($selectedId) {
            $election = Election::find($selectedId);
            if ($election) {
                return $election;
            }
            // Stale reference (election was deleted) — clear it and fall through.
            session()->forget('admin_current_election_id');
        }

        $fallback = Election::openOne() ?? Election::latest('id')->first();

        if ($fallback) {
            session(['admin_current_election_id' => $fallback->id]);
        }

        return $fallback;
    }
}
