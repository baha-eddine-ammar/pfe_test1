<?php

namespace Database\Factories;

use App\Models\DepartmentHeadInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentHeadInvite>
 */
class DepartmentHeadInviteFactory extends Factory
{
    protected $model = DepartmentHeadInvite::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'invited_email' => fake()->unique()->safeEmail(),
            'invited_by_user_id' => User::factory()->departmentHead(),
            'code_hash' => Hash::make('AUTHCODE12'),
            'reveal_token_hash' => Hash::make('reveal-token-123'),
            'reveal_used_at' => null,
            'used_at' => null,
            'expires_at' => now()->addDay(),
            'revoked_at' => null,
            'used_by_user_id' => null,
            'failed_attempts' => 0,
            'last_attempt_at' => null,
        ];
    }

    public function revealed(): static
    {
        return $this->state(fn () => [
            'reveal_used_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn () => [
            'used_at' => now(),
            'used_by_user_id' => User::factory()->departmentHead(),
        ]);
    }
}
