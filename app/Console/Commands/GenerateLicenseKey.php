<?php

namespace App\Console\Commands;

use App\Support\License;
use Illuminate\Console\Command;

class GenerateLicenseKey extends Command
{
    /**
     * php artisan license:generate
     */
    protected $signature = 'license:generate';

    protected $description = "Print this installation's license key (derived from APP_KEY)";

    public function handle(): int
    {
        if (! config('app.key')) {
            $this->error('APP_KEY is not set. Run `php artisan key:generate` first.');

            return self::FAILURE;
        }

        $key = License::expectedKey();

        $this->newLine();
        $this->line('  License key for this installation:');
        $this->newLine();
        $this->line("    <fg=green;options=bold>{$key}</>");
        $this->newLine();
        $this->line('  Enter this on the /license/activate screen to unlock the system.');
        $this->line('  This key is tied to this install\'s APP_KEY — copying the app to a new');
        $this->line('  server with a different APP_KEY will require generating a new one.');
        $this->newLine();

        return self::SUCCESS;
    }
}
