<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class SaveContactAction
{
    public function __invoke(User $actor, Customer $customer, array $input, ?Contact $contact = null): Contact
    {
        Gate::forUser($actor)->authorize('update', $customer);
        if ($contact) {
            Gate::forUser($actor)->authorize('update', $contact);
            abort_unless((int) $contact->companie_id === $customer->id, 403);
        }
        $data = Validator::make($input, CrmRules::contact($contact !== null))->validate();

        return DB::transaction(function () use ($actor, $customer, $contact, $data): Contact {
            $record = $contact ?? new Contact(['user_id' => $actor->id, 'companie_id' => $customer->id]);
            $record->fill($data)->save();
            app(RecordActivity::class)($actor, $customer, $contact ? 'contact_updated' : 'contact_added', 'Contact '.($contact ? 'updated.' : 'added.'), ['contact_id' => $record->id]);

            return $record->refresh();
        }, 3);
    }
}
