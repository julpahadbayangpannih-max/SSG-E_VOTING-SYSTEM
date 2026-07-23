<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * END-TO-END VERIFIABILITY.
     *
     * `ballots.commitment` is a SHA-256 hash of (election_id + receipt_code +
     * the voter's canonical choices), computed once at vote time inside the
     * same locked transaction that writes the Ballot row (see
     * Ballot::computeCommitment() / VoterDashboardController::submitVote()).
     * It reveals nothing about who the voter chose — only someone who
     * already knows the receipt code (i.e. the voter themselves) can ever
     * recompute it. It exists so a Merkle tree can be built over every
     * ballot's commitment without ever touching the votes table.
     *
     * `elections.merkle_root` is the root of that Merkle tree, frozen the
     * moment an admin closes the election (Admin\ElectionController::close()).
     * Freezing it turns "did anything change after results were certified"
     * into a single hash comparison: recompute the root from the ballots
     * table at any later time and it must match exactly, or something was
     * added, removed, or edited after certification. This is the same
     * tamper-evidence idea as the audit log hash chain, applied to the
     * ballot box instead of the log.
     */
    public function up(): void
    {
        Schema::table('ballots', function (Blueprint $table) {
            $table->char('commitment', 64)->nullable()->unique()->after('receipt_code');
        });

        Schema::table('elections', function (Blueprint $table) {
            $table->char('merkle_root', 64)->nullable()->after('status');
            $table->unsignedInteger('merkle_leaf_count')->nullable()->after('merkle_root');
            $table->timestamp('results_locked_at')->nullable()->after('merkle_leaf_count');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn(['merkle_root', 'merkle_leaf_count', 'results_locked_at']);
        });

        Schema::table('ballots', function (Blueprint $table) {
            $table->dropColumn('commitment');
        });
    }
};
