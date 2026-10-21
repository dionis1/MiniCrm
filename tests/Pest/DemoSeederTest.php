<?php

use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates repeatable demo records while preserving personal data and edits', function () {
    $personal = Lead::factory()->create(['name' => 'Personal prospect']);
    $this->seed(DemoSeeder::class);
    $sales = User::where('email', 'sales@demo.minicrm.test')->firstOrFail();
    expect(Lead::where('user_id', $sales->id)->count())->toBe(10)
        ->and(CrmTask::where('user_id', $sales->id)->count())->toBe(12);
    $counts = [Lead::count(), CrmTask::count(), CrmActivity::count(), User::count()];
    $draft = Lead::where('user_id', $sales->id)->where('email', 'lead-1@demo.minicrm.test')->firstOrFail();
    $draft->update(['name' => 'My demo edit']);
    $this->seed(DemoSeeder::class);
    expect([Lead::count(), CrmTask::count(), CrmActivity::count(), User::count()])->toBe($counts)
        ->and($draft->fresh()->name)->toBe('My demo edit')
        ->and($personal->fresh()->name)->toBe('Personal prospect');
    $this->actingAs($sales)->getJson('/api/v1/leads?per_page=100')->assertOk()->assertJsonCount(10, 'data')
        ->assertJsonMissing(['name' => 'Private workspace example'])
        ->assertJsonMissing(['name' => 'Personal prospect']);
});

it('never creates public demo credentials in production', function () {
    $this->app->instance('env', 'production');
    expect(fn () => app(DemoSeeder::class)())->toThrow(RuntimeException::class, 'only available')
        ->and(User::count())->toBe(0);
});
