<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    protected $fillable = [
        'type',
        'source',
        'title',
        'period_start',
        'period_end',
        'status',
        'generated_by',
        'generated_at',
        'summary',
        'metrics_snapshot',
        'anomalies',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'generated_at' => 'datetime',
            'metrics_snapshot' => 'array',
            'anomalies' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function aiSummaries(): HasMany
    {
        return $this->hasMany(ReportAiSummary::class);
    }

    public function latestAiSummary(): HasOne
    {
        return $this->hasOne(ReportAiSummary::class)->latestOfMany();
    }
}
