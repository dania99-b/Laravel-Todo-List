<?php

namespace App\Providers;

use App\Modules\Todo\Repositories\EloquentTodoRepository;
use App\Modules\Todo\Repositories\TodoRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TodoRepositoryInterface::class, EloquentTodoRepository::class);
    }
}
