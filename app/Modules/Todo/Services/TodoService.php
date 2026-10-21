<?php

namespace App\Modules\Todo\Services;

use App\Modules\Todo\DTO\TodoData;
use App\Modules\Todo\DTO\TodoFilters;
use App\Modules\Todo\Enums\TodoStatus;
use App\Modules\Todo\Models\Todo;
use App\Modules\Todo\Repositories\TodoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Application service (use cases) for the Todo module. Contains the
 * business workflows; HTTP and persistence concerns live elsewhere.
 */
class TodoService
{
    public function __construct(
        private readonly TodoRepositoryInterface $todos,
    ) {}

    /** @return LengthAwarePaginator<int, Todo> */
    public function listForUser(int $userId, TodoFilters $filters): LengthAwarePaginator
    {
        return $this->todos->paginateForUser($userId, $filters);
    }

    public function getForUser(int $userId, int $todoId): Todo
    {
        $todo = $this->todos->findForUser($userId, $todoId);

        if ($todo === null) {
            throw (new ModelNotFoundException)->setModel(Todo::class, [$todoId]);
        }

        return $todo;
    }

    public function createForUser(int $userId, TodoData $data): Todo
    {
        return DB::transaction(
            fn () => $this->todos->createForUser($userId, $data->toArray())
        );
    }

    public function update(Todo $todo, TodoData $data): Todo
    {
        return DB::transaction(
            fn () => $this->todos->update($todo, $data->toArray())
        );
    }

    public function updateStatus(Todo $todo, TodoStatus $status): Todo
    {
        return DB::transaction(
            fn () => $this->todos->update($todo, ['status' => $status->value])
        );
    }

    public function delete(Todo $todo): void
    {
        DB::transaction(fn () => $this->todos->delete($todo));
    }
}
