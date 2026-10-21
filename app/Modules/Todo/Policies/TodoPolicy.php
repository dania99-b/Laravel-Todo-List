<?php

namespace App\Modules\Todo\Policies;

use App\Models\User;
use App\Modules\Todo\Models\Todo;

class TodoPolicy
{
    public function view(User $user, Todo $todo): bool
    {
        return $todo->user_id === $user->id;
    }

    public function update(User $user, Todo $todo): bool
    {
        return $todo->user_id === $user->id;
    }

    public function delete(User $user, Todo $todo): bool
    {
        return $todo->user_id === $user->id;
    }
}
