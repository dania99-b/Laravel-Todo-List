<?php

namespace App\Modules\Todo\Repositories;

use App\Modules\Todo\DTO\TodoFilters;
use App\Modules\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data-access contract for todos. The application service depends on this
 * abstraction (DIP) so the persistence mechanism can be swapped — e.g. when
 * the Todo module is extracted into its own microservice — without touching
 * business logic.
 */
interface TodoRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Todo> */
    public function paginateForUser(int $userId, TodoFilters $filters): LengthAwarePaginator;

    public function findForUser(int $userId, int $todoId): ?Todo;

    /** @param array<string, mixed> $attributes */
    public function createForUser(int $userId, array $attributes): Todo;

    /** @param array<string, mixed> $attributes */
    public function update(Todo $todo, array $attributes): Todo;

    public function delete(Todo $todo): void;
}
