<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolutionAttachment extends Model
{
    protected $fillable = [
        'solution_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }
}
