<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_tasks', 'server_room')) {
                $table->string('server_room')->after('id');
            }

            if (! Schema::hasColumn('maintenance_tasks', 'maintenance_date')) {
                $table->dateTime('maintenance_date')->after('server_room');
            }

            if (! Schema::hasColumn('maintenance_tasks', 'fix_description')) {
                $table->longText('fix_description')->after('maintenance_date');
            }

            if (! Schema::hasColumn('maintenance_tasks', 'priority')) {
                $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->after('fix_description');
            }

            if (! Schema::hasColumn('maintenance_tasks', 'status')) {
                $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])
                    ->default('pending')
                    ->after('priority');
            }

            if (! Schema::hasColumn('maintenance_tasks', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')
                    ->after('status')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('maintenance_tasks', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')
                    ->after('assigned_to_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_tasks', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }

            if (Schema::hasColumn('maintenance_tasks', 'assigned_to_user_id')) {
                $table->dropConstrainedForeignId('assigned_to_user_id');
            }

            if (Schema::hasColumn('maintenance_tasks', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('maintenance_tasks', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('maintenance_tasks', 'fix_description')) {
                $table->dropColumn('fix_description');
            }

            if (Schema::hasColumn('maintenance_tasks', 'maintenance_date')) {
                $table->dropColumn('maintenance_date');
            }

            if (Schema::hasColumn('maintenance_tasks', 'server_room')) {
                $table->dropColumn('server_room');
            }
        });
    }
};
