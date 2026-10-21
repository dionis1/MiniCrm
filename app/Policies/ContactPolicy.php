<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $record): bool
    {
        return (int) $record->user_id === $user->id && Customer::whereKey($record->companie_id)->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Contact $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Contact $record): bool
    {
        return $this->view($user, $record);
    }
}
