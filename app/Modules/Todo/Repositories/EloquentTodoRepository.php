<?php

namespace App\Modules\Todo\Repositories;

use App\Modules\Todo\DTO\TodoFilters;
use App\Modules\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentTodoRepository implements TodoRepositoryInterface
{
    public function paginateForUser(int $userId, TodoFilters $filters): LengthAwarePaginator
    {
        return Todo::query()
            ->where('user_id', $userId)
            ->when($filters->status, fn ($q, $status) => $q->where('status', $status->value))
            ->when($filters->priority, fn ($q, $priority) => $q->where('priority', $priority->value))
            ->when($filters->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($filters->dueDateFrom, fn ($q, $from) => $q->where('due_date', '>=', $from))
            ->when($filters->dueDateTo, fn ($q, $to) => $q->where('due_date', '<=', $to))
            ->orderBy($filters->sortBy, $filters->sortDirection)
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function findForUser(int $userId, int $todoId): ?Todo
    {
        return Todo::query()
            ->where('user_id', $userId)
            ->whereKey($todoId)
            ->first();
    }

    public function createForUser(int $userId, array $attributes): Todo
    {
        return Todo::create([...$attributes, 'user_id' => $userId]);
    }

    public function update(Todo $todo, array $attributes): Todo
    {
        $todo->update($attributes);

        return $todo;
    }

    public function delete(Todo $todo): void
    {
        $todo->delete();
    }
}
