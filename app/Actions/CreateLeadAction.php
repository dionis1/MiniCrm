<?php

namespace App\Actions;

use App\Models\Lead;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateLeadAction
{
    public function __invoke(User $actor, array $input): Lead
    {
        Gate::forUser($actor)->authorize('create', Lead::class);
        $data = Validator::make($input, CrmRules::lead(false))->validate();

        return DB::transaction(function () use ($actor, $data): Lead {
            $record = Lead::create(array_merge($data, ['user_id' => $actor->id]));
            app(RecordActivity::class)($actor, $record, 'lead_created', 'Lead created.');

            return $record->refresh();
        }, 3);
    }
}
