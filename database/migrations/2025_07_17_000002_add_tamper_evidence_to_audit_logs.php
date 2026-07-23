<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AUDIT LOG TAMPER-EVIDENCE.
     *
     * Before this migration, audit_logs was a plain table — an admin (or
     * anyone with DB access) could UPDATE or DELETE a row and nothing would
     * ever show it happened. That defeats the point of an audit trail for
     * an e-voting system: the log is supposed to be the thing you trust
     * *especially* when you don't trust everyone with access to the DB.
     *
     * This adds a simple hash chain (same idea as a blockchain's linking,
     * scaled down to fit a single MySQL table):
     *   - every row stores `hash`, computed from its own content + the
     *     previous row's hash (`prev_hash`)
     *   - changing any field in any past row changes that row's hash, which
     *     no longer matches what the next row recorded as `prev_hash` — so
     *     the break is detectable by recomputing and walking the chain
     *     (see AuditLog::verifyChainIntegrity() / `php artisan audit:verify`)
     *   - `audit_chain_state` holds exactly one row: the hash of the latest
     *     entry, locked with lockForUpdate() during writes so concurrent
     *     log writes still form one single, correctly ordered chain instead
     *     of racing and forking
     *   - `audit_chain_checkpoints` exists because audit-logs:cleanup
     *     legitimately deletes old rows on a retention schedule — deleting
     *     rows would otherwise look identical to tampering. A checkpoint
     *     records the hash the chain should resume from after a cleanup, so
     *     verification can still confirm nothing was removed *outside* of
     *     that recorded, legitimate cleanup event.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->char('prev_hash', 64)->nullable()->after('ip_address');
            $table->char('hash', 64)->nullable()->unique()->after('prev_hash');
        });

        Schema::create('audit_chain_state', function (Blueprint $table) {
            // Single-row table by convention (id is always 1). Locked with
            // lockForUpdate() to serialize concurrent audit log writes so
            // the hash chain never forks.
            $table->id();
            $table->char('last_hash', 64);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('audit_chain_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deleted_up_to_audit_log_id');
            $table->unsignedInteger('entries_removed');
            // The hash the chain should resume from immediately after this
            // cleanup — i.e. the hash of the last row that got deleted.
            $table->char('resuming_prev_hash', 64);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Genesis: the chain's starting anchor before any entry exists.
        // A fixed, well-known all-zero value — never a real SHA-256 output
        // (which would need an actual preimage), so it can't be confused
        // with a legitimate prior entry's hash.
        DB::table('audit_chain_state')->insert([
            'id' => 1,
            'last_hash' => str_repeat('0', 64),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_checkpoints');
        Schema::dropIfExists('audit_chain_state');
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['prev_hash', 'hash']);
        });
    }
};
