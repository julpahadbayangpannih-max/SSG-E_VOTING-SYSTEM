<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Production maintenance: delete audit logs older than 1 year, every Sunday at 2AM.
// Keeps the audit_logs table from growing forever and slowing down queries/filters.
Schedule::command('audit-logs:cleanup --days=365')
    ->weeklyOn(0, '02:00')
    ->onOneServer()
    ->withoutOverlapping();

// TAMPER-EVIDENCE: run the full integrity check daily — audit log hash
// chain (see audit:verify) plus certified election Merkle roots plus basic
// DB/config health, all in one report. A non-zero exit code means
// something needs a human to look at it: an edited/deleted audit_logs row,
// a certified election's ballot set that no longer matches its frozen
// root, or a production config problem. Runs after the weekly cleanup's
// checkpoint would already be recorded, so a legitimate Sunday cleanup
// never trips this on its own.
$integrityCheckSchedule = Schedule::command('system:integrity-check')
    ->dailyAt('03:00')
    ->onOneServer()
    ->withoutOverlapping();

if ($adminAlertEmail = env('ADMIN_ALERT_EMAIL')) {
    $integrityCheckSchedule->emailOutputOnFailure($adminAlertEmail);
}

// Production maintenance: daily database backup at 1AM, keep the 14 most
// recent dumps on disk. NOTE: for real disaster recovery, storage/app/backups
// should also be synced to off-site storage (S3, another server, etc.) —
// see README for setup notes.
Schedule::command('db:backup --keep=14')
    ->dailyAt('01:00')
    ->onOneServer()
    ->withoutOverlapping();
