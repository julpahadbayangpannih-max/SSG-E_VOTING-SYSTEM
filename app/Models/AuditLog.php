<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'details',
        'ip_address',
        'created_at',
    ];

    // TAMPER-EVIDENCE: hash/prev_hash are never mass-assignable. They're
    // computed and written exclusively by LogsActivity::auditLog() using
    // forceFill(), inside a transaction that locks AuditChainState — the
    // same trusted-server-write pattern used for guarded fields elsewhere
    // in this codebase (Vote.election_id, Voter.is_approved, etc). If a
    // caller could set these directly, they could forge a chain that
    // "verifies" around tampered content.
    protected $guarded = ['hash', 'prev_hash'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Decode JSON details automatically.
     */
    public function getDetailsDecodedAttribute(): array
    {
        return $this->details ? json_decode($this->details, true) : [];
    }

    /**
     * The genesis anchor the chain starts from when there are no entries
     * and no cleanup checkpoints yet. A fixed, obviously-not-a-real-SHA-256
     * value (not derived from any content), so it can never collide with a
     * legitimate entry's hash.
     */
    public static function genesisHash(): string
    {
        return str_repeat('0', 64);
    }

    /**
     * The single canonical way this codebase turns one audit log entry's
     * content into its hash. Used both when writing an entry
     * (LogsActivity::auditLog()) and when verifying the chain
     * (verifyChainIntegrity()) — those two call sites must never diverge,
     * or every entry would "fail" verification even with no tampering.
     */
    public static function computeHash(
        string $prevHash,
        string $action,
        string $actorType,
        ?int $actorId,
        string $actorName,
        ?string $detailsJson,
        ?string $ipAddress,
        string $createdAt
    ): string {
        $payload = implode('|', [
            $prevHash,
            $action,
            $actorType,
            $actorId === null ? 'null' : (string) $actorId,
            $actorName,
            $detailsJson ?? 'null',
            $ipAddress ?? 'null',
            $createdAt,
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Walk the entire chain (from the latest cleanup checkpoint, or from
     * the very beginning if none exist) and confirm every entry's hash
     * still matches its recorded content and its predecessor.
     *
     * Returns:
     *   'ok'                 => true if nothing is wrong at all
     *   'broken_entries'      => ids whose stored hash no longer matches
     *                            their recomputed content — i.e. that row
     *                            was edited after being written
     *   'chain_state_mismatch'=> true if the last entry's hash doesn't match
     *                            AuditChainState — i.e. entries were added,
     *                            removed, or reordered outside of a
     *                            recorded cleanup checkpoint
     *
     * Deliberately does NOT cascade one broken entry into flagging every
     * entry after it: the chain continues from each row's *stored* hash
     * (not the recomputed one), so a single tampered row is isolated
     * instead of drowning the report in false positives.
     */
    public static function verifyChainIntegrity(): array
    {
        $checkpoint = AuditChainCheckpoint::orderByDesc('id')->first();

        $expectedPrev = $checkpoint ? $checkpoint->resuming_prev_hash : self::genesisHash();

        // Rows created before this feature shipped have no hash at all —
        // they predate the chain and were never going to be covered by it.
        // Report them separately instead of flagging every one as "broken."
        $legacyUnprotectedCount = self::whereNull('hash')->count();

        $query = self::whereNotNull('hash')->orderBy('id');
        if ($checkpoint) {
            $query->where('id', '>', $checkpoint->deleted_up_to_audit_log_id);
        }

        $brokenEntries = [];
        $lastHash = $expectedPrev;

        foreach ($query->cursor() as $log) {
            $createdAtString = $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '';

            $recomputed = self::computeHash(
                $expectedPrev,
                $log->action,
                $log->actor_type,
                $log->actor_id,
                $log->actor_name,
                $log->details,
                $log->ip_address,
                $createdAtString
            );

            if ($log->prev_hash !== $expectedPrev || $log->hash !== $recomputed) {
                $brokenEntries[] = $log->id;
            }

            // Continue from the row's own stored hash (not the recomputed
            // one) so a single tampered entry doesn't cascade into flagging
            // every entry that legitimately follows it.
            $expectedPrev = $log->hash ?? $expectedPrev;
            $lastHash = $expectedPrev;
        }

        $state = AuditChainState::find(1);
        $chainStateMismatch = ! $state || $state->last_hash !== $lastHash;

        return [
            'ok' => empty($brokenEntries) && ! $chainStateMismatch,
            'broken_entries' => $brokenEntries,
            'chain_state_mismatch' => $chainStateMismatch,
            'legacy_unprotected_count' => $legacyUnprotectedCount,
        ];
    }
}
