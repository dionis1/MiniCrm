<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class LogCustomerActivityAction
{
    public function __invoke(User $actor, Customer $customer, array $input): void
    {
        Gate::forUser($actor)->authorize('update', $customer);
        $data = Validator::make($input, [
            'type' => ['required', Rule::in(['call', 'note', 'email', 'meeting'])],
            'description' => ['required', 'string', 'max:3000'],
        ])->validate();
        app(RecordActivity::class)($actor, $customer, $data['type'], $data['description']);
    }
}
