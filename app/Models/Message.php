<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{

//These fields are allowed to be inserted from user input
    protected $fillable = [
        'user_id',
        'body',
    ];

    //Each message belongs to one user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
