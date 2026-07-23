<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ballots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_id')->constrained('voters')->cascadeOnDelete();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->timestamp('voted_at')->useCurrent();

            // This is what actually enforces "one vote per voter per
            // election" at the database level — the same protection
            // pattern votes(voter_id, position_id, candidate_id) already
            // used, just one level up.
            $table->unique(['voter_id', 'election_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ballots');
    }
};
