<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Todo\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — API Gateway / entry point
|
| Routing and HTTP concerns only; business logic lives in the modules'
| application services. Rate limiting is applied to auth endpoints.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('todos', TodoController::class)->except(['create', 'edit']);
        Route::patch('todos/{todo}/status', [TodoController::class, 'updateStatus'])
            ->name('todos.status');
    });
});
