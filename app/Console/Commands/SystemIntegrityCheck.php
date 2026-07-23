<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Ballot;
use App\Models\Election;
use App\Services\MerkleTree;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemIntegrityCheck extends Command
{
    /**
     * php artisan system:integrity-check
     *
     * A single "is everything actually okay right now" command, meant to be
     * run on a schedule (cron/monitoring) or manually before/after an
     * election. Combines every tamper-evidence and health signal this
     * system has into one pass/fail report:
     *
     *   1. Database connectivity
     *   2. Audit log hash-chain integrity      (existing: audit:verify)
     *   3. Certified election Merkle roots     (new: recompute vs frozen)
     *   4. Config sanity for a production deployment
     */
    protected $signature = 'system:integrity-check';

    protected $description = 'Run all integrity checks: DB health, audit log chain, certified election Merkle roots, and production config sanity';

    public function handle(): int
    {
        $this->info('JRMSU SSG E-Voting — System Integrity Check');
        $this->line(str_repeat('─', 60));

        $overallOk = true;

        $overallOk = $this->checkDatabase() && $overallOk;
        $overallOk = $this->checkAuditChain() && $overallOk;
        $overallOk = $this->checkElectionCertifications() && $overallOk;
        $overallOk = $this->checkConfig() && $overallOk;

        $this->line(str_repeat('─', 60));

        if ($overallOk) {
            $this->info('✔ All checks passed. System integrity looks good.');

            return self::SUCCESS;
        }

        $this->error('✘ One or more integrity checks FAILED. See above.');

        return self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        $this->line('');
        $this->comment('[1/4] Database connectivity');

        try {
            DB::connection()->getPdo();
            $this->info('  ✔ Connected.');

            return true;
        } catch (\Throwable $e) {
            $this->error('  ✘ Could not connect to the database: ' . $e->getMessage());

            return false;
        }
    }

    private function checkAuditChain(): bool
    {
        $this->line('');
        $this->comment('[2/4] Audit log hash chain');

        $result = AuditLog::verifyChainIntegrity();

        if ($result['legacy_unprotected_count'] > 0) {
            $this->comment(
                "  Note: {$result['legacy_unprotected_count']} entr" .
                ($result['legacy_unprotected_count'] === 1 ? 'y predates' : 'ies predate') .
                ' the tamper-evidence feature and are not covered.'
            );
        }

        if ($result['ok']) {
            $this->info('  ✔ Chain intact. No tampering detected.');

            return true;
        }

        if (! empty($result['broken_entries'])) {
            $this->error('  ✘ Entries edited after being written (audit_logs.id): ' . implode(', ', $result['broken_entries']));
        }

        if ($result['chain_state_mismatch']) {
            $this->error('  ✘ Chain state mismatch — entries were added, removed, or reordered outside a recorded cleanup.');
        }

        return false;
    }

    private function checkElectionCertifications(): bool
    {
        $this->line('');
        $this->comment('[3/4] Certified election Merkle roots');

        $certified = Election::whereNotNull('results_locked_at')->whereNotNull('merkle_root')->get();

        if ($certified->isEmpty()) {
            $this->line('  No certified (closed) elections yet — nothing to check.');

            return true;
        }

        $ok = true;

        foreach ($certified as $election) {
            $commitments = Ballot::where('election_id', $election->id)
                ->whereNotNull('commitment')
                ->pluck('commitment')
                ->all();

            $liveRoot = MerkleTree::buildRoot($commitments);
            $matches = $liveRoot && hash_equals($election->merkle_root, $liveRoot);

            if ($matches) {
                $this->info("  ✔ \"{$election->title}\" (#{$election->id}): root matches certified tally ({$election->merkle_leaf_count} ballots).");
            } else {
                $ok = false;
                $this->error("  ✘ \"{$election->title}\" (#{$election->id}): LIVE ROOT DOES NOT MATCH CERTIFIED ROOT.");
                $this->error('     This means the ballots table changed after results were certified — investigate immediately.');
                $this->line("     Certified root: {$election->merkle_root}");
                $this->line('     Live root:      ' . ($liveRoot ?? '(none — no commitments found)'));
            }
        }

        return $ok;
    }

    private function checkConfig(): bool
    {
        $this->line('');
        $this->comment('[4/4] Production config sanity');

        $ok = true;
        $env = config('app.env');
        $debug = config('app.debug');

        if ($env === 'production' && $debug) {
            $ok = false;
            $this->error('  ✘ APP_DEBUG is true in a production environment. This can leak stack traces, .env values, and query details to attackers. Set APP_DEBUG=false.');
        } else {
            $this->info("  ✔ APP_ENV={$env}, APP_DEBUG=" . ($debug ? 'true' : 'false') . ' — consistent.');
        }

        if ($env === 'production' && ! config('session.secure')) {
            $this->comment('  ⚠ session.secure is not enabled. If this is served over HTTPS (it should be), consider SESSION_SECURE_COOKIE=true.');
        }

        return $ok;
    }
}
