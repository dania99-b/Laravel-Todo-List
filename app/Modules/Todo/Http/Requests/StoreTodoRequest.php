<?php

namespace App\Modules\Todo\Http\Requests;

use App\Modules\Todo\DTO\TodoData;
use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TodoStatus::class)],
            'priority' => ['sometimes', Rule::enum(TodoPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function todoData(): TodoData
    {
        return TodoData::fromArray($this->validated());
    }
}
