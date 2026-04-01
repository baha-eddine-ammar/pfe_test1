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
        'net_rx_mbps',
        'net_tx_mbps',
    ];

    protected function casts(): array
    {
        return [
            'cpu_percent' => 'float',
            'disk_used_gb' => 'float',
            'disk_total_gb' => 'float',
            'net_rx_mbps' => 'float',
            'net_tx_mbps' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
