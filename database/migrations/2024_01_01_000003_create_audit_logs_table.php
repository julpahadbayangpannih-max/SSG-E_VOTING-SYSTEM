<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');               // e.g. 'voter_approved', 'vote_submitted'
            $table->string('actor_type');           // 'admin' | 'voter'
            $table->unsignedBigInteger('actor_id')->nullable(); // null for failed/anonymous actions
            $table->string('actor_name');
            $table->text('details')->nullable();    // JSON: context info
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
