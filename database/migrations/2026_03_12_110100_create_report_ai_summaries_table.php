<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_ai_summaries')) {
            return;
        }

        Schema::create('report_ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('status')->default('fallback');
            $table->longText('summary_text');
            $table->json('observations')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('report_ai_summaries')) {
            Schema::dropIfExists('report_ai_summaries');
        }
    }
};
