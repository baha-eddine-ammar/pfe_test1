<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'cpu_percent',
        'ram_used_mb',
        'ram_total_mb',
        'disk_used_gb',
        'disk_total_gb',
        'storage_free_gb',
        'storage_total_gb',
        'temperature_c',
        'net_rx_mbps',
        'net_tx_mbps',
        'network_connected',
        'network_name',
        'network_speed_mbps',
        'network_ipv4',
        'uptime_seconds',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_percent' => 'float',
            'disk_used_gb' => 'float',
            'disk_total_gb' => 'float',
            'storage_free_gb' => 'float',
            'storage_total_gb' => 'float',
            'temperature_c' => 'float',
            'net_rx_mbps' => 'float',
            'net_tx_mbps' => 'float',
            'network_connected' => 'boolean',
            'network_speed_mbps' => 'integer',
            'uptime_seconds' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
