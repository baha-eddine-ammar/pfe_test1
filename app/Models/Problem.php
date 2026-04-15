<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Problem extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];


        //This problem belongs to ONE user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


        //One problem → has MANY solutions
        //so u can get $problem->user
    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class);
    }

    //One problem → has MANY files (attachments)

    public function attachments(): HasMany
    {
        return $this->hasMany(ProblemAttachment::class);
    }
}
