<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            if (! Schema::hasColumn('servers', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (! Schema::hasColumn('servers', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('identifier');
            }

            if (! Schema::hasColumn('servers', 'server_type')) {
                $table->string('server_type')->nullable()->after('ip_address');
            }
        });

        Schema::table('server_metrics', function (Blueprint $table): void {
            if (! Schema::hasColumn('server_metrics', 'server_id')) {
                $table->unsignedBigInteger('server_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('server_metrics', 'cpu_percent')) {
                $table->decimal('cpu_percent', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'ram_used_mb')) {
                $table->unsignedInteger('ram_used_mb')->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'ram_total_mb')) {
                $table->unsignedInteger('ram_total_mb')->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'disk_used_gb')) {
                $table->decimal('disk_used_gb', 8, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'disk_total_gb')) {
                $table->decimal('disk_total_gb', 8, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'net_rx_mbps')) {
                $table->decimal('net_rx_mbps', 8, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'net_tx_mbps')) {
                $table->decimal('net_tx_mbps', 8, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'storage_free_gb')) {
                $table->decimal('storage_free_gb', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'storage_total_gb')) {
                $table->decimal('storage_total_gb', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'temperature_c')) {
                $table->decimal('temperature_c', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'network_connected')) {
                $table->boolean('network_connected')->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'network_name')) {
                $table->string('network_name')->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'network_speed_mbps')) {
                $table->unsignedInteger('network_speed_mbps')->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'network_ipv4')) {
                $table->string('network_ipv4', 45)->nullable();
            }

            if (! Schema::hasColumn('server_metrics', 'uptime_seconds')) {
                $table->unsignedBigInteger('uptime_seconds')->nullable();
            }

            if (
                Schema::hasColumn('server_metrics', 'server_id')
                && Schema::hasColumn('server_metrics', 'created_at')
                && ! $this->indexExists('server_metrics', 'server_metrics_server_created_at_index')
            ) {
                $table->index(['server_id', 'created_at'], 'server_metrics_server_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_metrics', function (Blueprint $table): void {
            if ($this->indexExists('server_metrics', 'server_metrics_server_created_at_index')) {
                $table->dropIndex('server_metrics_server_created_at_index');
            }

            foreach ([
                'uptime_seconds',
                'network_ipv4',
                'network_speed_mbps',
                'network_name',
                'network_connected',
                'temperature_c',
                'storage_total_gb',
                'storage_free_gb',
                'net_tx_mbps',
                'net_rx_mbps',
                'disk_total_gb',
                'disk_used_gb',
                'ram_total_mb',
                'ram_used_mb',
                'cpu_percent',
                'server_id',
            ] as $column) {
                if (Schema::hasColumn('server_metrics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('servers', function (Blueprint $table): void {
            foreach ([
                'server_type',
                'ip_address',
                'description',
            ] as $column) {
                if (Schema::hasColumn('servers', $column)) {
                    $table->dropColumn($column);
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
