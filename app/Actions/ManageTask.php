<?php

namespace App\Actions;

use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageTask
{
    public function create(User $user, array $data): CrmTask
    {
        return DB::transaction(function () use ($user, $data): CrmTask {
            $task = CrmTask::create(array_merge($data, ['user_id' => $user->id]));
            $this->log($user, $task, 'task_created');

            return $task;
        });
    }

    public function update(User $user, CrmTask $task, array $data): CrmTask
    {
        return DB::transaction(function () use ($user, $task, $data): CrmTask {
            $task->update($data);
            $this->log($user, $task, 'task_updated');

            return $task->refresh();
        });
    }

    public function complete(User $user, CrmTask $task, bool $completed): CrmTask
    {
        return DB::transaction(function () use ($user, $task, $completed): CrmTask {
            $task->update(['completed_at' => $completed ? now() : null]);
            $this->log($user, $task, $completed ? 'task_completed' : 'task_reopened');

            return $task->refresh();
        });
    }

    private function log(User $user, CrmTask $task, string $type): void
    {
        CrmActivity::create([
            'user_id' => $user->id,
            'subject_type' => 'task',
            'subject_id' => $task->id,
            'type' => $type,
            'description' => $task->title,
        ]);
    }
}
