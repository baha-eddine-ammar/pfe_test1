<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_readings', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id')->index();
            $table->decimal('voltage_v', 8, 2);
            $table->decimal('current_a', 10, 3);
            $table->decimal('power_w', 10, 2);
            $table->decimal('energy_kwh', 12, 4)->nullable();
            $table->decimal('frequency_hz', 6, 2)->nullable();
            $table->decimal('power_factor', 5, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('power_readings');
    }
};
