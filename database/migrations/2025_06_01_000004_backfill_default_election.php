<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data migration: if this install already had positions/candidates/votes
     * from before multi-election support existed, wrap all of that in one
     * real Election row ("Election #1") instead of losing it. Fresh installs
     * with no existing data just skip this — there's nothing to backfill.
     */
    public function up(): void
    {
        $hasExistingData = DB::table('positions')->exists()
            || DB::table('candidates')->exists()
            || DB::table('votes')->exists();

        if (! $hasExistingData) {
            return;
        }

        $startRaw = DB::table('settings')->where('setting_key', 'start_time')->value('setting_value');
        $endRaw = DB::table('settings')->where('setting_key', 'end_time')->value('setting_value');
        $startTs = $startRaw ? strtotime($startRaw) : null;
        $endTs = $endRaw ? strtotime($endRaw) : null;
        $now = time();
        $hasVotes = DB::table('votes')->exists();

        // Best-effort guess at what state this election was actually in,
        // based on whatever schedule/votes already existed. An admin can
        // always fix the status by hand afterward from the Elections page.
        if ($endTs && $now > $endTs) {
            $status = 'closed';
        } elseif ($startTs && $now >= $startTs && (! $endTs || $now <= $endTs)) {
            $status = 'open';
        } elseif ($hasVotes) {
            // Votes exist but no clean schedule to reason about — keep it
            // "open" so has_voted-equivalent state (about to move to
            // ballots) stays meaningful instead of silently freezing it.
            $status = 'open';
        } else {
            $status = 'draft';
        }

        $electionId = DB::table('elections')->insertGetId([
            'title' => 'Election #1 (migrated)',
            'start_time' => $startTs ? date('Y-m-d H:i:s', $startTs) : null,
            'end_time' => $endTs ? date('Y-m-d H:i:s', $endTs) : null,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->update(['election_id' => $electionId]);
        DB::table('candidates')->update(['election_id' => $electionId]);
        DB::table('votes')->update(['election_id' => $electionId]);

        // has_voted still exists on voters at this point in the migration
        // sequence — the next migration drops it after this one reads it.
        $votedVoterIds = DB::table('voters')->where('has_voted', true)->pluck('id');

        $rows = $votedVoterIds->map(fn ($voterId) => [
            'voter_id' => $voterId,
            'election_id' => $electionId,
            'voted_at' => now(),
        ])->all();

        if ($rows) {
            DB::table('ballots')->insert($rows);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible — reconstructing exactly which votes
        // belonged to "no election" is not meaningful once other elections
        // may have been created since. Rolling back the schema migrations
        // that follow this one is enough for a dev-environment rollback.
    }
};
