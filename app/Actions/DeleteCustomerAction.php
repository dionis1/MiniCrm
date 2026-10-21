<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteCustomerAction
{
    public function __invoke(User $actor, Customer $record): void
    {
        Gate::forUser($actor)->authorize('delete', $record);
        DB::transaction(function () use ($actor, $record): void {
            app(RecordActivity::class)($actor, $record, 'customer_deleted', 'Customer deleted.');
            $record->delete();
        }, 3);
    }
}
