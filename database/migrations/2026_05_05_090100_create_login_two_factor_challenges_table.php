<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_two_factor_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_two_factor_challenges');
    }
};
