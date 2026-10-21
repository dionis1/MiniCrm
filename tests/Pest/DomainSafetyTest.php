<?php

use App\Actions\CompleteTaskAction;
use App\Actions\ConvertLeadAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateTaskAction;
use App\Actions\DeleteCustomerAction;
use App\Actions\SaveContactAction;
use App\Actions\UpdateLeadAction;
use App\Enums\LeadStage;
use App\Enums\TaskPriority;
use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('rejects cross-owner writes even when actions are called without HTTP controllers', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $owner->id]);
    $customer = Customer::factory()->create(['user_id' => $owner->id]);
    expect(fn () => app(UpdateLeadAction::class)($other, $lead, ['name' => 'Intrusion', 'version' => 0]))->toThrow(AuthorizationException::class);
    expect(fn () => app(ConvertLeadAction::class)($other, $lead, 0))->toThrow(AuthorizationException::class);
    expect(fn () => app(DeleteCustomerAction::class)($other, $customer))->toThrow(AuthorizationException::class);
    expect(fn () => app(SaveContactAction::class)($other, $customer, ['first_name' => 'A', 'last_name' => 'B']))->toThrow(AuthorizationException::class);
    expect(CrmActivity::count())->toBe(0)->and(Customer::count())->toBe(1);
});

it('validates action input and scoped task relationships at the domain boundary', function () {
    $owner = User::factory()->create();
    $lead = Lead::factory()->create();
    expect(fn () => app(CreateTaskAction::class)($owner, ['title' => 'Private attachment', 'lead_id' => $lead->id]))->toThrow(ValidationException::class);
    expect(fn () => app(CreateCustomerAction::class)($owner, ['name' => '', 'status' => 'invalid']))->toThrow(ValidationException::class);
    expect(CrmTask::count())->toBe(0)->and(Customer::count())->toBe(0);
});

it('rolls back the conversion claim and all records if contact creation fails', function () {
    $lead = Lead::factory()->create();
    Contact::creating(fn () => throw new RuntimeException('Contact write failed'));
    try {
        expect(fn () => app(ConvertLeadAction::class)($lead->owner, $lead, 0))->toThrow(RuntimeException::class);
    } finally {
        Contact::flushEventListeners();
    }
    expect(Customer::count())->toBe(0)->and(Contact::count())->toBe(0)
        ->and(CrmActivity::count())->toBe(0)->and($lead->fresh()->converted_at)->toBeNull()
        ->and($lead->fresh()->version)->toBe(0);
});

it('converts the claimed current version rather than a stale model snapshot', function () {
    $stale = Lead::factory()->create(['company_name' => 'Before']);
    $owner = $stale->owner;
    app(UpdateLeadAction::class)($owner, $stale->fresh(), ['company_name' => 'After', 'version' => 0]);
    $customer = app(ConvertLeadAction::class)($owner, $stale, 1);
    expect($customer->name)->toBe('After')->and($stale->fresh()->stage)->toBe(LeadStage::ClosedWon);
});

it('rolls back a customer write when its audit record cannot be saved', function () {
    $owner = User::factory()->create();
    CrmActivity::creating(fn () => throw new RuntimeException('Audit unavailable'));
    try {
        expect(fn () => app(CreateCustomerAction::class)($owner, ['name' => 'Atomic customer']))->toThrow(RuntimeException::class);
    } finally {
        CrmActivity::flushEventListeners();
    }
    expect(Customer::count())->toBe(0);
});

it('completes a task idempotently and keeps enum values in the domain', function () {
    $task = CrmTask::factory()->create(['priority' => 'high']);
    $owner = User::findOrFail($task->user_id);
    $complete = app(CompleteTaskAction::class);
    $complete($owner, $task, true);
    $firstCompletion = $task->fresh()->completed_at;
    $this->travel(1)->hours();
    $complete($owner, $task, true);
    expect($task->fresh()->completed_at->equalTo($firstCompletion))->toBeTrue()
        ->and($task->fresh()->priority)->toBe(TaskPriority::High)
        ->and(CrmActivity::where('type', 'task_completed')->count())->toBe(1);
});
