<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'device_id',
        'temperature',
        'humidity',
        'recorded_at',
    ];


    //Convert temperature into decimal number.
    //when reading data from database,
    //convert values into correct types
    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'humidity' => 'float',
            'recorded_at' => 'datetime',
        ];
    }
}
