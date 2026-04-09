<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone_number')->nullable()->after('department');
            });
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('role');
            });
        }

        DB::table('users')
            ->where('role', 'it_staff')
            ->update(['role' => 'staff']);

        if (Schema::hasColumn('users', 'status') && Schema::hasColumn('users', 'is_approved')) {
            DB::table('users')
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '');
                })
                ->update([
                    'status' => DB::raw("CASE WHEN is_approved = 1 THEN 'approved' ELSE 'pending' END"),
                ]);

            DB::table('users')
                ->where('is_approved', true)
                ->where('status', 'pending')
                ->update(['status' => 'approved']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone_number');
            });
        }
    }
};
