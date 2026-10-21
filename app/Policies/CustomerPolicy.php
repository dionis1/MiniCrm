<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $record): bool
    {
        return (int) $record->user_id === $user->id;
    }

    public function update(User $user, Customer $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Customer $record): bool
    {
        return $this->view($user, $record);
    }
}
