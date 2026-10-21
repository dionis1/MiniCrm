<?php

namespace App\Actions;

use App\Enums\LeadStage;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ConvertLeadAction
{
    public function __invoke(User $actor, Lead $record, int $version): Customer
    {
        Gate::forUser($actor)->authorize('update', $record);

        return DB::transaction(function () use ($actor, $record, $version): Customer {
            $claimed = Lead::whereKey($record->id)->where('user_id', $actor->id)
                ->where('version', $version)->whereNull('converted_at')->whereNull('converted_company_id')
                ->update(['version' => $version + 1, 'stage' => LeadStage::ClosedWon->value, 'converted_at' => now()]);
            if ($claimed !== 1) {
                throw new ConflictHttpException('Lead was already changed or converted.');
            }
            // Use the claimed database version, never a potentially stale caller's model.
            $record->refresh();
            $customer = Customer::create([
                'user_id' => $actor->id, 'name' => $record->company_name ?: $record->name,
                'email' => $record->email, 'phone' => $record->phone, 'status' => 'active',
            ]);
            $parts = explode(' ', trim($record->name), 2);
            Contact::create([
                'user_id' => $actor->id, 'companie_id' => $customer->id,
                'first_name' => $parts[0], 'last_name' => $parts[1] ?? '',
                'email' => $record->email, 'phone' => $record->phone,
            ]);
            $record->forceFill(['converted_company_id' => $customer->id])->save();
            app(RecordActivity::class)($actor, $record, 'converted', 'Lead converted to customer #'.$customer->id, ['customer_id' => $customer->id, 'version' => $version + 1]);
            app(RecordActivity::class)($actor, $customer, 'created', 'Customer created from lead #'.$record->id);

            return $customer;
        }, 3);
    }
}
