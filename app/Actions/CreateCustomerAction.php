<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateCustomerAction
{
    public function __invoke(User $actor, array $input): Customer
    {
        Gate::forUser($actor)->authorize('create', Customer::class);
        $data = Validator::make($input, CrmRules::customer(false))->validate();

        return DB::transaction(function () use ($actor, $data): Customer {
            $record = Customer::create(array_merge($data, ['user_id' => $actor->id]));
            app(RecordActivity::class)($actor, $record, 'customer_created', 'Customer created.');

            return $record->refresh();
        }, 3);
    }
}
