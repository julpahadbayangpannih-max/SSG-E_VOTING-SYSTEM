<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BALLOT SECRECY FIX: a vote row no longer records who cast it.
     *
     * Before this migration, `votes.voter_id` meant anyone with database
     * access (an admin, a DB backup, a breach) could run
     * `SELECT * FROM votes WHERE voter_id = X` and see exactly which
     * candidate a specific voter chose. That's a real election-integrity
     * problem even though every other write path was already locked down.
     *
     * "Has this voter voted" already has its own dedicated, correctly-scoped
     * home: the `ballots` table (voter_id + election_id, written inside the
     * locked transaction in VoterDashboardController::submitVote()). That
     * table proves *that* someone voted, for turnout/duplicate-prevention
     * purposes, without saying *what* they voted for. Votes themselves only
     * need to know which election/position/candidate they belong to — not
     * whose they are.
     *
     * NOTE: written defensively (checks what actually exists first) because
     * the drop order has three mutual dependencies (FK needs the unique
     * index, the column can't drop while it's in the unique index) and an
     * earlier partial run can leave any subset of these already removed.
     */
    public function up(): void
    {
        $foreignKeyExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'votes')
            ->where('CONSTRAINT_NAME', 'votes_voter_id_foreign')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($foreignKeyExists) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropForeign(['voter_id']);
            });
        }

        $indexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'votes')
            ->where('INDEX_NAME', 'votes_voter_id_position_id_candidate_id_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropUnique(['voter_id', 'position_id', 'candidate_id']);
            });
        }

        if (Schema::hasColumn('votes', 'voter_id')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropColumn('voter_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            // NOTE: reversing this re-introduces the ballot-secrecy gap.
            // voter_id is nullable on the way back down because existing
            // vote rows have no voter to backfill it with.
            $table->foreignId('voter_id')->nullable()->after('id')
                ->constrained('voters')->cascadeOnDelete();
        });
    }
};
