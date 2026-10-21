<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;
use App\Modules\Todo\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Todo> */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Medium,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => TodoStatus::Completed]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => TodoPriority::High]);
    }
}
