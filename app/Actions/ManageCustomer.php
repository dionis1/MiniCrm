<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageCustomer
{
    public function create(User $user, array $data): Customer
    {
        return DB::transaction(function () use ($user, $data): Customer {
            $customer = Customer::create(array_merge($data, ['user_id' => $user->id]));
            $this->note($user, $customer, 'created', 'Customer created.');

            return $customer;
        });
    }

    public function update(User $user, Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($user, $customer, $data): Customer {
            $customer->update($data);
            $this->note($user, $customer, 'updated', 'Customer updated.');

            return $customer->refresh();
        });
    }

    public function contact(User $user, Customer $customer, array $data): Contact
    {
        return DB::transaction(function () use ($user, $customer, $data): Contact {
            $contact = Contact::create(array_merge($data, [
                'user_id' => $user->id,
                'companie_id' => $customer->id,
            ]));
            $this->note($user, $customer, 'contact_added', 'Contact added.');

            return $contact;
        });
    }

    public function note(User $user, Customer $customer, string $type, string $description): void
    {
        CrmActivity::create([
            'user_id' => $user->id,
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'type' => $type,
            'description' => $description,
        ]);
    }
}
