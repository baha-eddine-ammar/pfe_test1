<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAiSummary extends Model
{
    protected $fillable = [
        'report_id',
        'provider',
        'model',
        'status',
        'summary_text',
        'observations',
        'recommendations',
        'error_message',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'observations' => 'array',
            'recommendations' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
