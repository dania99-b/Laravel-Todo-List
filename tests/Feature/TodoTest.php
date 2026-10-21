<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Todo\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('api')->plainTextToken];
    }

    public function test_user_can_create_todo(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/todos', [
            'title' => 'Buy milk',
            'description' => 'Oat milk',
            'priority' => 'high',
            'due_date' => '2026-12-01 10:00:00',
        ], $this->auth($user))->assertCreated()
            ->assertJsonPath('data.title', 'Buy milk')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('todos', ['title' => 'Buy milk', 'user_id' => $user->id]);
    }

    public function test_create_todo_validates_input(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/todos', [
            'title' => '',
            'priority' => 'urgent',
        ], $this->auth($user))->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority']);
    }

    public function test_user_lists_only_own_todos(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Todo::factory()->count(3)->create(['user_id' => $user->id]);
        Todo::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->getJson('/api/v1/todos', $this->auth($user))->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_show_own_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->getJson("/api/v1/todos/{$todo->id}", $this->auth($user))
            ->assertOk()->assertJsonPath('data.id', $todo->id);
    }

    public function test_user_cannot_view_another_users_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->getJson("/api/v1/todos/{$todo->id}", $this->auth($user))->assertForbidden();
    }

    public function test_user_cannot_update_or_delete_another_users_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->putJson("/api/v1/todos/{$todo->id}", [
            'title' => 'Hijacked',
            'status' => 'completed',
            'priority' => 'low',
        ], $this->auth($user))->assertForbidden();

        $this->patchJson("/api/v1/todos/{$todo->id}/status", [
            'status' => 'completed',
        ], $this->auth($user))->assertForbidden();

        $this->deleteJson("/api/v1/todos/{$todo->id}", [], $this->auth($user))->assertForbidden();

        $this->assertDatabaseMissing('todos', ['title' => 'Hijacked']);
        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    public function test_user_can_update_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Old']);

        $this->putJson("/api/v1/todos/{$todo->id}", [
            'title' => 'New title',
            'description' => null,
            'status' => 'completed',
            'priority' => 'low',
            'due_date' => null,
        ], $this->auth($user))->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'New title']);
    }

    public function test_user_can_update_todo_status(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->patchJson("/api/v1/todos/{$todo->id}/status", ['status' => 'completed'], $this->auth($user))
            ->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_update_status_rejects_invalid_value(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->patchJson("/api/v1/todos/{$todo->id}/status", ['status' => 'done'], $this->auth($user))
            ->assertUnprocessable()->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_delete_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/todos/{$todo->id}", [], $this->auth($user))->assertNoContent();

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_missing_todo_returns_404(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/v1/todos/9999', $this->auth($user))->assertNotFound();
    }

    public function test_filtering_by_status_and_priority(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'priority' => 'high']);
        Todo::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'pending', 'priority' => 'low']);

        $this->getJson('/api/v1/todos?status=completed', $this->auth($user))
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/todos?priority=low', $this->auth($user))
            ->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/todos?status=bogus', $this->auth($user))
            ->assertUnprocessable();
    }

    public function test_search_matches_title_and_description(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Call the dentist']);
        Todo::factory()->create(['user_id' => $user->id, 'description' => 'book dentist appointment']);

        $this->getJson('/api/v1/todos?search=dentist', $this->auth($user))
            ->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_due_date_range_filter(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'due_date' => '2026-01-10 00:00:00']);
        Todo::factory()->create(['user_id' => $user->id, 'due_date' => '2026-06-10 00:00:00']);

        $this->getJson('/api/v1/todos?due_date_from=2026-06-01&due_date_to=2026-06-30', $this->auth($user))
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sorting_and_pagination(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Bravo']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Alpha']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Charlie']);

        $response = $this->getJson('/api/v1/todos?sort_by=title&sort_direction=asc&per_page=2', $this->auth($user))
            ->assertOk();

        $this->assertSame(['Alpha', 'Bravo'], array_column($response->json('data'), 'title'));
        $this->assertSame(2, $response->json('meta.per_page'));
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_sorting_rejects_non_whitelisted_field(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/v1/todos?sort_by=password', $this->auth($user))
            ->assertUnprocessable();
    }

    public function test_todo_belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($todo->user->is($user));
        $this->assertTrue($user->todos->contains($todo));
    }
}
