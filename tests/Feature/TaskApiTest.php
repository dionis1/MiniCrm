<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_completion_and_dashboard_metrics(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = $this->postJson('/api/v1/tasks', [
            'title' => 'Call client', 'priority' => 'high', 'due_at' => now()->subDay()->toIso8601String(),
        ])->assertCreated();
        $id = $task->json('data.id');
        $this->getJson('/api/v1/tasks?filter=overdue')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dashboard/metrics')->assertJsonPath('data.tasks_overdue', 1);
        $this->patchJson("/api/v1/tasks/$id/complete", ['completed' => true])
            ->assertOk()->assertJsonPath('data.completed', true);
        $this->getJson('/api/v1/tasks?filter=overdue')->assertJsonCount(0, 'data');
    }

    public function test_task_cannot_be_attached_to_someone_elses_lead(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $lead = Lead::create(['user_id' => $owner->id, 'name' => 'Hidden']);
        Sanctum::actingAs($other);
        $this->postJson('/api/v1/tasks', ['title' => 'Spy', 'lead_id' => $lead->id])
            ->assertUnprocessable()->assertJsonValidationErrors('lead_id');
    }

    public function test_task_visibility_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/tasks', ['title' => 'Private task'])->json('data.id');
        Sanctum::actingAs($other);
        $this->getJson("/api/v1/tasks/$id")->assertNotFound();
        $this->getJson('/api/v1/tasks')->assertJsonCount(0, 'data');
    }
}
