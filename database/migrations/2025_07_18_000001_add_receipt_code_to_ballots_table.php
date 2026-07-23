<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ballots', function (Blueprint $table) {
            // Voter-facing proof of participation. Deliberately NOT a proof
            // of which candidates were chosen (ballot secrecy is preserved —
            // this column lives on Ballot, never on Vote). Nullable so
            // existing rows created before this migration don't break.
            $table->string('receipt_code', 20)->nullable()->unique()->after('voted_at');
        });
    }

    public function down(): void
    {
        Schema::table('ballots', function (Blueprint $table) {
            $table->dropColumn('receipt_code');
        });
    }
};
