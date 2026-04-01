<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('server_metrics')) {
            return;
        }

        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->decimal('cpu_percent', 5, 2);
            $table->unsignedInteger('ram_used_mb');
            $table->unsignedInteger('ram_total_mb');
            $table->decimal('disk_used_gb', 8, 2);
            $table->decimal('disk_total_gb', 8, 2);
            $table->decimal('net_rx_mbps', 8, 2);
            $table->decimal('net_tx_mbps', 8, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('server_metrics')) {
            Schema::dropIfExists('server_metrics');
        }
    }
};
