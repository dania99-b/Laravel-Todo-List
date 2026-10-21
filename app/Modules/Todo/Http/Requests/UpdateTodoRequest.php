<?php

namespace App\Modules\Todo\Http\Requests;

use App\Modules\Todo\DTO\TodoData;
use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT replaces the todo: all fields are required to describe the
 * full new state, consistent with HTTP PUT semantics.
 */
class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('todo'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(TodoStatus::class)],
            'priority' => ['required', Rule::enum(TodoPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function todoData(): TodoData
    {
        return TodoData::fromArray($this->validated());
    }
}
