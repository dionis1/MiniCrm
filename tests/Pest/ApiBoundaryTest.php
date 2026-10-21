<?php

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates filters, bounds pages, and rejects unsupported sort columns', function () {
    $this->actingAs(User::factory()->create());
    foreach (['/leads?per_page=101', '/leads?page=-1', '/leads?stage=invalid', '/customers?sort=password', '/customers?page=0', '/tasks?priority=urgent'] as $url) {
        $this->getJson('/api/v1'.$url)->assertUnprocessable()->assertJsonStructure(['message', 'errors']);
    }
});

it('keeps pipeline pagination complete and owner scoped', function () {
    $owner = User::factory()->create();
    Lead::factory()->count(27)->create(['user_id' => $owner->id]);
    Lead::factory()->create(['name' => 'Hidden customer']);
    $this->actingAs($owner);
    $first = $this->getJson('/api/v1/leads?per_page=25')->assertOk()->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 27);
    $second = $this->getJson('/api/v1/leads?per_page=25&page=2')->assertOk()->assertJsonCount(2, 'data');
    expect(array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))->toBe([]);
    $this->getJson('/api/v1/search?q=Hidden')->assertOk()->assertJsonCount(0, 'data.leads');
});

it('returns JSON authentication errors even when the client does not request JSON', function () {
    $this->get('/api/v1/leads', ['Accept' => 'text/html'])->assertUnauthorized()->assertJsonStructure(['message']);
});

it('rejects an unknown Sanctum bearer token without a database error', function () {
    $this->withToken('invalid-token')->getJson('/api/v1/leads')->assertUnauthorized();
});
