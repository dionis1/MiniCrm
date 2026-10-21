<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class UpdateCustomerAction
{
    public function __invoke(User $actor, Customer $record, array $input): Customer
    {
        Gate::forUser($actor)->authorize('update', $record);
        $data = Validator::make($input, CrmRules::customer(true))->validate();

        return DB::transaction(function () use ($actor, $data, $record): Customer {
            $record->update($data);
            app(RecordActivity::class)($actor, $record, 'customer_updated', 'Customer updated.');

            return $record->refresh();
        }, 3);
    }
}
