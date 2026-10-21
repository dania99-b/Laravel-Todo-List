<?php

namespace App\Modules\Todo\Http\Controllers;

use App\Modules\Todo\Http\Requests\IndexTodoRequest;
use App\Modules\Todo\Http\Requests\StoreTodoRequest;
use App\Modules\Todo\Http\Requests\UpdateTodoRequest;
use App\Modules\Todo\Http\Requests\UpdateTodoStatusRequest;
use App\Modules\Todo\Http\Resources\TodoResource;
use App\Modules\Todo\Models\Todo;
use App\Modules\Todo\Services\TodoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TodoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TodoService $todos,
    ) {}

    public function index(IndexTodoRequest $request): AnonymousResourceCollection
    {
        $todos = $this->todos->listForUser($request->user()->id, $request->filters());

        return TodoResource::collection($todos);
    }

    public function store(StoreTodoRequest $request): JsonResponse
    {
        $todo = $this->todos->createForUser($request->user()->id, $request->todoData());

        return (new TodoResource($todo))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Todo $todo): TodoResource
    {
        $this->authorize('view', $todo);

        return new TodoResource($todo);
    }

    public function update(UpdateTodoRequest $request, Todo $todo): TodoResource
    {
        return new TodoResource($this->todos->update($todo, $request->todoData()));
    }

    public function updateStatus(UpdateTodoStatusRequest $request, Todo $todo): TodoResource
    {
        return new TodoResource($this->todos->updateStatus($todo, $request->status()));
    }

    public function destroy(Todo $todo): Response
    {
        $this->authorize('delete', $todo);

        $this->todos->delete($todo);

        return response()->noContent();
    }
}
