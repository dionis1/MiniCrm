<?php

namespace App\Actions;

use App\Models\CrmTask;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class UpdateTaskAction
{
    public function __invoke(User $actor, CrmTask $record, array $input): CrmTask
    {
        Gate::forUser($actor)->authorize('update', $record);
        $data = Validator::make($input, CrmRules::task($actor, true))->validate();

        return DB::transaction(function () use ($actor, $data, $record): CrmTask {
            $record->update($data);
            app(RecordActivity::class)($actor, $record, 'task_updated', 'Task updated.');

            return $record->refresh();
        }, 3);
    }
}
