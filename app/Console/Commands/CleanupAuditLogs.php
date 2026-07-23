<?php

namespace App\Console\Commands;

use App\Models\AuditChainCheckpoint;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAuditLogs extends Command
{
    /**
     * php artisan audit-logs:cleanup
     * php artisan audit-logs:cleanup --days=180
     */
    protected $signature = 'audit-logs:cleanup {--days=365 : Records older than this many days will be deleted}';

    protected $description = 'Delete audit log entries older than the given retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $toDelete = AuditLog::where('created_at', '<', $cutoff);
        $count = $toDelete->count();

        if ($count === 0) {
            $this->info("No audit logs older than {$days} days. Nothing to clean up.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toDelete, $count, $days) {
            // TAMPER-EVIDENCE: record where the hash chain should resume
            // from *before* deleting anything. Without this, "500 old rows
            // legitimately purged by a scheduled retention job" and "500
            // rows someone deleted to hide something" would look exactly
            // the same to verifyChainIntegrity() — this checkpoint is what
            // tells them apart. See AuditLog::verifyChainIntegrity().
            $lastToDelete = (clone $toDelete)->orderByDesc('id')->first();

            AuditChainCheckpoint::create([
                'deleted_up_to_audit_log_id' => $lastToDelete->id,
                'entries_removed' => $count,
                // Rows written before the hash-chain feature existed have
                // no hash — fall back to genesis so a checkpoint created
                // against legacy data doesn't record a bogus null anchor.
                'resuming_prev_hash' => $lastToDelete->hash ?? AuditLog::genesisHash(),
                'note' => "Retention cleanup: entries older than {$days} days",
            ]);

            $toDelete->delete();
        });

        $this->info("Deleted {$count} audit log entries older than {$days} days.");
        $this->info('A chain checkpoint was recorded so `php artisan audit:verify` can still confirm nothing else was tampered with.');

        return self::SUCCESS;
    }
}
