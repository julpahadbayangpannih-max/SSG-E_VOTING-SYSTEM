<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable for now — the next migration (backfill) fills these in for
        // existing rows, then the migration after that locks them to NOT NULL.
        // Doing it in three steps avoids ever having a NOT NULL column with no
        // value to put in it partway through the upgrade.
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('election_id')->nullable()->after('id')
                ->constrained('elections')->cascadeOnDelete();
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('election_id')->nullable()->after('id')
                ->constrained('elections')->cascadeOnDelete();
        });

        Schema::table('votes', function (Blueprint $table) {
            // Denormalized on purpose (also reachable via position_id) — lets
            // per-election queries/exports/deletes avoid a join.
            $table->foreignId('election_id')->nullable()->after('id')
                ->constrained('elections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('election_id');
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('election_id');
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('election_id');
        });
    }
};
