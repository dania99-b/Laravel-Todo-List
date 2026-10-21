<?php

namespace App\Modules\Auth\DTO;

final readonly class Credentials
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /** @param array<string, mixed> $data validated request data */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }
}
