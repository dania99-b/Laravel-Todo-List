<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTO\Credentials;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function credentials(): Credentials
    {
        return Credentials::fromArray($this->validated());
    }
}
