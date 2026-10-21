<?php

namespace App\Modules\Todo\DTO;

use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;

/**
 * Carries validated write data for a todo between the HTTP layer
 * and the application service.
 */
final readonly class TodoData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public TodoStatus $status,
        public TodoPriority $priority,
        public ?string $dueDate,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated request data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            status: isset($data['status']) ? TodoStatus::from($data['status']) : TodoStatus::Pending,
            priority: isset($data['priority']) ? TodoPriority::from($data['priority']) : TodoPriority::Medium,
            dueDate: $data['due_date'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->dueDate,
        ];
    }
}
