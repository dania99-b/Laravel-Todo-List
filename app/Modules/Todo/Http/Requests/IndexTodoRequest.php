<?php

namespace App\Modules\Todo\Http\Requests;

use App\Modules\Todo\DTO\TodoFilters;
use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TodoStatus::class)],
            'priority' => ['sometimes', Rule::enum(TodoPriority::class)],
            'search' => ['sometimes', 'string', 'max:255'],
            'due_date_from' => ['sometimes', 'date'],
            'due_date_to' => ['sometimes', 'date', 'after_or_equal:due_date_from'],
            'sort_by' => ['sometimes', Rule::in(TodoFilters::SORTABLE)],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.TodoFilters::MAX_PER_PAGE],
        ];
    }

    public function filters(): TodoFilters
    {
        return TodoFilters::fromArray($this->validated());
    }
}
