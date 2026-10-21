<?php

namespace App\Actions;

use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteTaskAction
{
    public function __invoke(User $actor, CrmTask $record): void
    {
        Gate::forUser($actor)->authorize('delete', $record);
        DB::transaction(function () use ($actor, $record): void {
            app(RecordActivity::class)($actor, $record, 'task_deleted', 'Task deleted.');
            $record->delete();
        }, 3);
    }
}
