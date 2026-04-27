<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'identifier',
        'ip_address',
        'server_type',
        'api_token',
        'last_seen_at',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function latestMetric(): HasOne
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('created_at');
    }
}
