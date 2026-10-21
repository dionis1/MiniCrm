<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class RecordActivity
{
    public function __invoke(User $actor, Model $record, string $type, string $description, array $metadata = []): void
    {
        CrmActivity::create([
            'user_id' => $actor->id,
            'subject_type' => match ($record::class) {
                Lead::class => 'lead',
                Customer::class => 'customer',
                Contact::class => 'contact',
                CrmTask::class => 'task',
            },
            'subject_id' => $record->id, 'type' => $type,
            'description' => $description, 'metadata' => $metadata,
        ]);
    }
}
