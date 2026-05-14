<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerReading extends Model
{
    protected $fillable = [
        'device_id',
        'voltage_v',
        'current_a',
        'power_w',
        'energy_kwh',
        'frequency_hz',
        'power_factor',
    ];

    protected function casts(): array
    {
        return [
            'voltage_v' => 'float',
            'current_a' => 'float',
            'power_w' => 'float',
            'energy_kwh' => 'float',
            'frequency_hz' => 'float',
            'power_factor' => 'float',
        ];
    }
}
