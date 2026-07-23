<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Now that the backfill migration has filled election_id in on every
        // existing row, lock the column down to NOT NULL. Only touching
        // nullability here (not re-declaring ->constrained()) so we don't
        // disturb the foreign key added in the earlier migration.
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable(false)->change();
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable(false)->change();
        });
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable(false)->change();
        });

        // has_voted moves off Voter entirely — the ballots table (voter_id,
        // election_id) is what now answers "has this voter voted, and in
        // which election," per voter per election instead of one global flag.
        Schema::table('voters', function (Blueprint $table) {
            $table->dropColumn('has_voted');
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->boolean('has_voted')->default(false);
        });
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable()->change();
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable()->change();
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedBigInteger('election_id')->nullable()->change();
        });
    }
};
