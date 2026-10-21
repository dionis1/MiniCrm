<?php

namespace App\Actions;

use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CompleteTaskAction
{
    public function __invoke(User $actor, CrmTask $record, bool $completed): CrmTask
    {
        Gate::forUser($actor)->authorize('update', $record);

        return DB::transaction(function () use ($actor, $record, $completed): CrmTask {
            $query = CrmTask::whereKey($record->id)->where('user_id', $actor->id);
            $completed ? $query->whereNull('completed_at') : $query->whereNotNull('completed_at');
            if ($query->update(['completed_at' => $completed ? now() : null]) === 1) {
                app(RecordActivity::class)($actor, $record, $completed ? 'task_completed' : 'task_reopened', 'Task '.($completed ? 'completed.' : 'reopened.'));
            }

            return $record->refresh();
        }, 3);
    }
}
