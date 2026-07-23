<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class VerifyAuditChain extends Command
{
    /**
     * php artisan audit:verify
     */
    protected $signature = 'audit:verify';

    protected $description = 'Verify the audit log hash chain has not been tampered with';

    public function handle(): int
    {
        $result = AuditLog::verifyChainIntegrity();

        if ($result['legacy_unprotected_count'] > 0) {
            $this->comment(
                "Note: {$result['legacy_unprotected_count']} entr" .
                ($result['legacy_unprotected_count'] === 1 ? 'y predates' : 'ies predate') .
                ' the tamper-evidence feature and are not covered by this check.'
            );
        }

        if ($result['ok']) {
            $this->info('✔ Audit log chain is intact. No tampering detected.');

            return self::SUCCESS;
        }

        $this->error('✘ Audit log chain integrity check FAILED.');

        if (! empty($result['broken_entries'])) {
            $this->error('The following audit_logs.id entries do not match their recorded hash (edited after being written):');
            $this->line('  ' . implode(', ', $result['broken_entries']));
        }

        if ($result['chain_state_mismatch']) {
            $this->error(
                'The last entry in the chain does not match the recorded chain state. ' .
                'This means entries were added, removed, or reordered outside of a recorded ' .
                'cleanup checkpoint (audit_chain_checkpoints).'
            );
        }

        return self::FAILURE;
    }
}
