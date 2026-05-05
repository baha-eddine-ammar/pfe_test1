<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_head_invites', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('invited_email')->index();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('reveal_token_hash');
            $table->dateTime('reveal_used_at')->nullable();
            $table->dateTime('used_at')->nullable()->index();
            $table->dateTime('expires_at')->index();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['invited_email', 'revoked_at', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_head_invites');
    }
};
