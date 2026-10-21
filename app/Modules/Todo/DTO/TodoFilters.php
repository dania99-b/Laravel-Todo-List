<?php

namespace App\Modules\Todo\DTO;

use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;

/**
 * Whitelisted filtering, sorting and pagination options for listing todos.
 * Only fields listed in SORTABLE may ever be used for ordering.
 */
final readonly class TodoFilters
{
    public const SORTABLE = ['title', 'status', 'priority', 'due_date', 'created_at', 'updated_at'];

    public const MAX_PER_PAGE = 100;

    public function __construct(
        public ?TodoStatus $status,
        public ?TodoPriority $priority,
        public ?string $search,
        public ?string $dueDateFrom,
        public ?string $dueDateTo,
        public string $sortBy,
        public string $sortDirection,
        public int $perPage,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated request data
     */
    public static function fromArray(array $data): self
    {
        $sortBy = $data['sort_by'] ?? 'created_at';
        if (! in_array($sortBy, self::SORTABLE, true)) {
            $sortBy = 'created_at';
        }

        $sortDirection = strtolower($data['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        return new self(
            status: isset($data['status']) ? TodoStatus::tryFrom($data['status']) : null,
            priority: isset($data['priority']) ? TodoPriority::tryFrom($data['priority']) : null,
            search: $data['search'] ?? null,
            dueDateFrom: $data['due_date_from'] ?? null,
            dueDateTo: $data['due_date_to'] ?? null,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            perPage: $perPage,
        );
    }
}
