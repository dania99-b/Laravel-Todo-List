<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTO\Credentials;
use App\Modules\Auth\DTO\RegisterUserData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Application service for the Authentication module: registration and
 * credential verification. Token mechanics are delegated to TokenService.
 */
class AuthService
{
    public function __construct(
        private readonly TokenService $tokens,
    ) {}

    /** @return array{user: User, token: string} */
    public function register(RegisterUserData $data): array
    {
        $user = DB::transaction(fn () => User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password, // hashed by the model's 'hashed' cast
        ]));

        return ['user' => $user, 'token' => $this->tokens->issueToken($user)];
    }

    /** @return array{user: User, token: string} */
    public function login(Credentials $credentials): array
    {
        $user = User::where('email', $credentials->email)->first();

        if (! $user || ! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return ['user' => $user, 'token' => $this->tokens->issueToken($user)];
    }

    public function logout(User $user): void
    {
        $this->tokens->revokeCurrentToken($user);
    }
}
