<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reports')) {
            return;
        }

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('source')->default('demo');
            $table->string('title');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->string('status')->default('generated');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->text('summary')->nullable();
            $table->json('metrics_snapshot');
            $table->json('anomalies')->nullable();
            $table->timestamps();

            $table->index(['type', 'generated_at']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('reports')) {
            Schema::dropIfExists('reports');
        }
    }
};
