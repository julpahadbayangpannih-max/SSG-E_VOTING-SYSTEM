<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // FIX: no fallback default credentials. A hardcoded default password
        // in the source code (even meant as a placeholder) becomes a known,
        // public credential the moment this repo is on GitHub. If the deploy
        // environment forgot to set these, fail loudly instead of silently
        // creating a guessable admin/change_me_now account.
        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');
        $name = env('ADMIN_NAME');

        if (! $username || ! $password || ! $name) {
            $this->command?->error(
                'ADMIN_USERNAME, ADMIN_PASSWORD, and ADMIN_NAME must all be set '
                . 'in the environment before seeding. Refusing to create an '
                . 'admin account with a default/guessable password.'
            );

            return;
        }

        if (strlen($password) < 8) {
            $this->command?->error('ADMIN_PASSWORD is too short (minimum 8 characters). Seeding aborted.');

            return;
        }

        DB::table('admins')->insertOrIgnore([
            'username' => $username,
            'password' => Hash::make($password),
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
