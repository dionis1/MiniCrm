<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $record): bool
    {
        return (int) $record->user_id === $user->id;
    }

    public function update(User $user, Lead $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Lead $record): bool
    {
        return $this->view($user, $record);
    }
}
