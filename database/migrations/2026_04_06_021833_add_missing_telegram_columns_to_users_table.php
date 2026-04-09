<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_chat_id')->nullable()->after('phone_number');
            });
        }

        if (! Schema::hasColumn('users', 'telegram_link_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_link_token')->nullable()->after('telegram_chat_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'telegram_link_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telegram_link_token');
            });
        }

        if (Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }
};
