<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            if (
                Schema::hasColumn('maintenance_tasks', 'assigned_to_user_id')
                && Schema::hasColumn('maintenance_tasks', 'maintenance_date')
                && ! $this->indexExists('maintenance_tasks', 'maintenance_tasks_assignee_date_index')
            ) {
                $table->index(['assigned_to_user_id', 'maintenance_date'], 'maintenance_tasks_assignee_date_index');
            }

            if (
                Schema::hasColumn('maintenance_tasks', 'status')
                && Schema::hasColumn('maintenance_tasks', 'maintenance_date')
                && ! $this->indexExists('maintenance_tasks', 'maintenance_tasks_status_date_index')
            ) {
                $table->index(['status', 'maintenance_date'], 'maintenance_tasks_status_date_index');
            }

            if (
                Schema::hasColumn('maintenance_tasks', 'priority')
                && Schema::hasColumn('maintenance_tasks', 'maintenance_date')
                && ! $this->indexExists('maintenance_tasks', 'maintenance_tasks_priority_date_index')
            ) {
                $table->index(['priority', 'maintenance_date'], 'maintenance_tasks_priority_date_index');
            }
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            if (
                Schema::hasColumn('server_metrics', 'server_id')
                && Schema::hasColumn('server_metrics', 'created_at')
                && ! $this->indexExists('server_metrics', 'server_metrics_server_created_at_index')
            ) {
                $table->index(['server_id', 'created_at'], 'server_metrics_server_created_at_index');
            }
        });

        Schema::table('messages', function (Blueprint $table): void {
            if (
                Schema::hasColumn('messages', 'user_id')
                && Schema::hasColumn('messages', 'id')
                && ! $this->indexExists('messages', 'messages_user_id_id_index')
            ) {
                $table->index(['user_id', 'id'], 'messages_user_id_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            if ($this->indexExists('messages', 'messages_user_id_id_index')) {
                $table->dropIndex('messages_user_id_id_index');
            }
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            if ($this->indexExists('server_metrics', 'server_metrics_server_created_at_index')) {
                $table->dropIndex('server_metrics_server_created_at_index');
            }
        });

        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            foreach ([
                'maintenance_tasks_assignee_date_index',
                'maintenance_tasks_status_date_index',
                'maintenance_tasks_priority_date_index',
            ] as $index) {
                if ($this->indexExists('maintenance_tasks', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return match (Schema::getConnection()->getDriverName()) {
            'mysql', 'mariadb' => ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index])),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => ($row->name ?? null) === $index),
            'pgsql' => ! empty(DB::select(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $index],
            )),
            'sqlsrv' => ! empty(DB::select(
                'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
                [$index, $table],
            )),
            default => false,
        };
    }
};
