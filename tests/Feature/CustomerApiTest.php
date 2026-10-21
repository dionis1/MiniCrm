<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_contact_crud_and_activity_feed(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'Acme', 'status' => 'active', 'tags' => ['enterprise'],
        ])->assertCreated()->assertJsonPath('data.tags.0', 'enterprise');
        $id = $response->json('data.id');
        $this->getJson('/api/v1/customers?tag=enterprise')->assertJsonCount(1, 'data');
        $contact = $this->postJson("/api/v1/customers/$id/contacts", [
            'first_name' => 'Ada', 'last_name' => 'Smith',
        ])->assertCreated();
        $this->patchJson('/api/v1/contacts/'.$contact->json('data.id'), ['position' => 'CTO'])->assertOk()
            ->assertJsonPath('data.position', 'CTO');
        $this->postJson("/api/v1/customers/$id/activities", [
            'type' => 'call', 'description' => 'Agreed on a proposal.',
        ])->assertCreated();
        $this->getJson("/api/v1/customers/$id/activities")->assertJsonCount(3, 'data');
    }

    public function test_other_user_cannot_access_customer_or_contacts(): void
    {
        $first = User::factory()->create();
        $other = User::factory()->create();
        $customer = Customer::create(['user_id' => $first->id, 'name' => 'Private']);
        Sanctum::actingAs($other);
        $this->getJson("/api/v1/customers/$customer->id")->assertNotFound();
        $this->postJson("/api/v1/customers/$customer->id/contacts", ['first_name' => 'X', 'last_name' => 'Y'])->assertNotFound();
        $this->getJson('/api/v1/customers')->assertJsonCount(0, 'data');
    }
}
