<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_metrics', function (Blueprint $table): void {
            if (
                Schema::hasColumn('server_metrics', 'created_at')
                && ! $this->indexExists('server_metrics', 'server_metrics_created_at_index')
            ) {
                $table->index('created_at', 'server_metrics_created_at_index');
            }
        });

        Schema::table('sensor_readings', function (Blueprint $table): void {
            if (
                Schema::hasColumn('sensor_readings', 'device_id')
                && Schema::hasColumn('sensor_readings', 'recorded_at')
                && ! $this->indexExists('sensor_readings', 'sensor_readings_device_recorded_at_index')
            ) {
                $table->index(['device_id', 'recorded_at'], 'sensor_readings_device_recorded_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            if ($this->indexExists('sensor_readings', 'sensor_readings_device_recorded_at_index')) {
                $table->dropIndex('sensor_readings_device_recorded_at_index');
            }
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            if ($this->indexExists('server_metrics', 'server_metrics_created_at_index')) {
                $table->dropIndex('server_metrics_created_at_index');
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
