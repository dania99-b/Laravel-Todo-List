<?php

namespace App\Modules\Todo\Models;

use App\Models\User;
use App\Modules\Todo\Enums\TodoPriority;
use App\Modules\Todo\Enums\TodoStatus;
use App\Modules\Todo\Policies\TodoPolicy;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(TodoPolicy::class)]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TodoStatus::class,
            'priority' => TodoPriority::class,
            'due_date' => 'datetime',
        ];
    }

    protected static function newFactory(): TodoFactory
    {
        return TodoFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === TodoStatus::Completed;
    }
}
