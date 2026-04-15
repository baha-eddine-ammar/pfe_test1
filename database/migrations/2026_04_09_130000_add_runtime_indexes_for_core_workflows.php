<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->index(['assigned_to_user_id', 'maintenance_date'], 'maintenance_tasks_assignee_date_index');
            $table->index(['status', 'maintenance_date'], 'maintenance_tasks_status_date_index');
            $table->index(['priority', 'maintenance_date'], 'maintenance_tasks_priority_date_index');
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            $table->index(['server_id', 'created_at'], 'server_metrics_server_created_at_index');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->index(['user_id', 'id'], 'messages_user_id_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_user_id_id_index');
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            $table->dropIndex('server_metrics_server_created_at_index');
        });

        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->dropIndex('maintenance_tasks_assignee_date_index');
            $table->dropIndex('maintenance_tasks_status_date_index');
            $table->dropIndex('maintenance_tasks_priority_date_index');
        });
    }
};
