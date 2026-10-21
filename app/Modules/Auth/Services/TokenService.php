<?php

namespace App\Modules\Auth\Services;

use App\Models\User;

/**
 * Single responsibility: issuing and revoking Sanctum access tokens.
 * Keeps token mechanics out of the authentication workflow so the
 * mechanism can change (e.g. per-service tokens, OAuth) in one place.
 */
class TokenService
{
    public function issueToken(User $user, string $name = 'api'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
