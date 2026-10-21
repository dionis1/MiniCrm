<?php

namespace App\Policies;

use App\Models\CrmTask;
use App\Models\User;

class CrmTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, CrmTask $record): bool
    {
        return (int) $record->user_id === $user->id;
    }

    public function update(User $user, CrmTask $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, CrmTask $record): bool
    {
        return $this->view($user, $record);
    }
}
