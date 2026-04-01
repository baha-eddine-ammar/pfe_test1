<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemAttachment extends Model
{
    protected $fillable = [
        'problem_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }
}
