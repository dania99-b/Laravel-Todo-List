<?php

namespace App\Modules\Todo\Http\Requests;

use App\Modules\Todo\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTodoStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('todo'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TodoStatus::class)],
        ];
    }

    public function status(): TodoStatus
    {
        return TodoStatus::from($this->validated('status'));
    }
}
