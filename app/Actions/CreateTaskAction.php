<?php

namespace App\Actions;

use App\Models\CrmTask;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateTaskAction
{
    public function __invoke(User $actor, array $input): CrmTask
    {
        Gate::forUser($actor)->authorize('create', CrmTask::class);
        $data = Validator::make($input, CrmRules::task($actor, false))->validate();

        return DB::transaction(function () use ($actor, $data): CrmTask {
            $record = CrmTask::create(array_merge($data, ['user_id' => $actor->id]));
            app(RecordActivity::class)($actor, $record, 'task_created', 'Task created.');

            return $record->refresh();
        }, 3);
    }
}
