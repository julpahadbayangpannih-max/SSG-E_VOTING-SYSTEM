<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    /**
     * php artisan db:backup
     * php artisan db:backup --keep=14
     */
    protected $signature = 'db:backup {--keep=14 : Number of most recent backups to keep on disk}';

    protected $description = 'Dump the database to storage/app/backups and prune old backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection !== 'mysql') {
            $this->error("This command currently only supports mysql. Current connection: {$connection}");

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = sprintf(
            '%s_%s.sql',
            $config['database'],
            now()->format('Y-m-d_His')
        );
        $path = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Password passed via MYSQL_PWD env var instead of --password= so it
        // doesn't show up in `ps aux` process listings on the server.
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($path)
        );

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['MYSQL_PWD' => $config['password']]
        );

        if (! is_resource($process)) {
            $this->error('Failed to start mysqldump process.');

            return self::FAILURE;
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! File::exists($path) || File::size($path) === 0) {
            $this->error('mysqldump failed: ' . trim($errorOutput));
            if (File::exists($path)) {
                File::delete($path);
            }

            return self::FAILURE;
        }

        $this->info("Backup created: {$filename} (" . round(File::size($path) / 1024, 1) . ' KB)');

        $this->pruneOldBackups($backupDir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Keep only the N most recent backup files; delete the rest.
     * This prevents the backups folder from growing forever (same
     * "data lifecycle" problem as logs and audit_logs).
     */
    protected function pruneOldBackups(string $dir, int $keep): void
    {
        $files = collect(File::files($dir))
            ->filter(fn ($f) => $f->getExtension() === 'sql')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        $toDelete = $files->slice($keep);

        foreach ($toDelete as $file) {
            File::delete($file->getPathname());
        }

        if ($toDelete->count() > 0) {
            $this->info("Pruned {$toDelete->count()} old backup(s), kept the {$keep} most recent.");
        }
    }
}
