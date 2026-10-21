<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_leads(): void
    {
        $this->getJson('/api/v1/leads')->assertUnauthorized();
    }

    public function test_owner_can_create_filter_and_move_lead(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/leads', [
            'name' => 'Ada', 'company_name' => 'Acme',
            'stage' => 'new', 'deal_value' => 1200, 'probability' => 40,
        ]);
        $response->assertCreated()->assertJsonPath('data.expected_value', 480);
        $id = $response->json('data.id');
        $this->getJson('/api/v1/leads?stage=new')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/api/v1/leads/'.$id.'/stage', ['stage' => 'proposal', 'version' => 0])
            ->assertOk()->assertJsonPath('data.stage', 'proposal');

        $this->patchJson('/api/v1/leads/'.$id.'/stage', ['stage' => 'closed_won', 'version' => 0])
            ->assertStatus(409);
        $this->assertDatabaseHas('leads', ['id' => $id, 'version' => 1]);
    }

    public function test_lead_conversion_is_atomic_and_cannot_be_repeated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $lead = Lead::create(['user_id' => $user->id, 'name' => 'Ada Writer', 'company_name' => 'Acme']);
        $this->postJson('/api/v1/leads/'.$lead->id.'/convert', ['version' => 0])
            ->assertCreated()->assertJsonPath('data.name', 'Acme');
        $this->postJson('/api/v1/leads/'.$lead->id.'/convert', ['version' => 0])->assertStatus(409);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_others_cannot_read_or_update_private_leads(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $lead = Lead::create(['user_id' => $owner->id, 'name' => 'Private']);
        Sanctum::actingAs($other);
        $this->getJson('/api/v1/leads/'.$lead->id)->assertNotFound();
        $this->patchJson('/api/v1/leads/'.$lead->id.'/stage', ['stage' => 'proposal', 'version' => 0])->assertNotFound();
        $this->getJson('/api/v1/leads')->assertJsonCount(0, 'data');
    }

    public function test_lead_page_size_is_bounded_for_kanban_clients(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        foreach (range(1, 3) as $number) {
            Lead::create(['user_id' => $owner->id, 'name' => 'Prospect '.$number]);
        }

        $this->getJson('/api/v1/leads?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
        $this->getJson('/api/v1/leads?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

}
