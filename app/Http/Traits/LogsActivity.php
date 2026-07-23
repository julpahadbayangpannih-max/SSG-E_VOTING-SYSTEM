<?php

namespace App\Http\Traits;

use App\Models\AuditChainState;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait LogsActivity
{
    /**
     * Write one audit log entry, tamper-evidently chained to the one
     * before it. See AuditLog::verifyChainIntegrity() for how the chain is
     * checked and app/Models/AuditLog.php for why hash/prev_hash are
     * guarded rather than fillable.
     *
     * @param string $action e.g. 'voter_approved'
     * @param string $actorType 'admin' | 'voter'
     * @param array $details any extra context
     */
    protected function auditLog(
        Request $request,
        string $action,
        string $actorType,
        ?int $actorId,
        string $actorName,
        array $details = []
    ): void {
        $detailsJson = $details ? json_encode($details) : null;
        $ipAddress = $request->ip();
        $now = now();
        // Matches the precision actually stored in the `created_at` column
        // (see migration) — hashing a more precise value than what's saved
        // would make every entry fail its own verification.
        $createdAt = $now->format('Y-m-d H:i:s');

        DB::transaction(function () use ($action, $actorType, $actorId, $actorName, $detailsJson, $ipAddress, $now, $createdAt) {
            // Lock the single chain-state row for the duration of this
            // transaction. Any other concurrent auditLog() call — from a
            // different request, different process — blocks here until
            // this one commits. Without this, two simultaneous writes could
            // both read the same "previous hash" and each build a valid but
            // divergent link, forking the chain instead of extending it.
            $state = AuditChainState::where('id', 1)->lockForUpdate()->first();
            $prevHash = $state->last_hash;

            $hash = AuditLog::computeHash(
                $prevHash,
                $action,
                $actorType,
                $actorId,
                $actorName,
                $detailsJson,
                $ipAddress,
                $createdAt
            );

            $log = new AuditLog([
                'action' => $action,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'details' => $detailsJson,
                'ip_address' => $ipAddress,
                'created_at' => $now,
            ]);
            $log->forceFill(['prev_hash' => $prevHash, 'hash' => $hash])->save();

            $state->forceFill(['last_hash' => $hash])->save();
        });
    }
}
