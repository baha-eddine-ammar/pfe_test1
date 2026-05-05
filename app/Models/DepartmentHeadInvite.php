<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentHeadInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'invited_email',
        'invited_by_user_id',
        'code_hash',
        'reveal_token_hash',
        'reveal_used_at',
        'used_at',
        'expires_at',
        'revoked_at',
        'used_by_user_id',
        'failed_attempts',
        'last_attempt_at',
    ];

    protected $hidden = [
        'code_hash',
        'reveal_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'reveal_used_at' => 'datetime',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function hasBeenRevealed(): bool
    {
        return $this->reveal_used_at !== null;
    }

    public function canReveal(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked() && ! $this->isUsed() && ! $this->hasBeenRevealed();
    }

    public function canBeRegistered(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked() && ! $this->isUsed() && $this->hasBeenRevealed();
    }
}
