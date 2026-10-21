<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ManageLead
{
    public function create(User $user, array $data): Lead
    {
        return DB::transaction(function () use ($user, $data): Lead {
            $lead = Lead::create(array_merge($data, ['user_id' => $user->id]));
            $this->activity($user, $lead, 'created', 'Lead created.');

            return $lead;
        });
    }

    public function update(User $user, Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($user, $lead, $data): Lead {
            $version = $data['version'];
            unset($data['version']);
            $changed = Lead::whereKey($lead->id)
                ->where('user_id', $user->id)
                ->where('version', $version)
                ->whereNull('converted_company_id')
                ->whereNull('converted_at')
                ->update(array_merge($data, ['version' => $version + 1]));

            if ($changed !== 1) {
                throw new ConflictHttpException('Lead was updated elsewhere. Refresh and retry.');
            }
            $fresh = $lead->fresh();
            $this->activity($user, $fresh, 'updated', 'Lead information updated.');

            return $fresh;
        });
    }

    public function convert(User $user, Lead $lead, int $version): Customer
    {
        return DB::transaction(function () use ($user, $lead, $version): Customer {
            // Claim the conversion first, before creating any customer records.
            // A failed optimistic update rolls back the complete transaction.
            $claimed = Lead::whereKey($lead->id)
                ->where('user_id', $user->id)
                ->where('version', $version)
                ->whereNull('converted_company_id')
                ->whereNull('converted_at')
                ->update(['version' => $version + 1, 'stage' => 'closed_won', 'converted_at' => now()]);
            if ($claimed !== 1) {
                throw new ConflictHttpException('Lead was already changed or converted.');
            }

            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $lead->company_name ?: $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => 'active',
            ]);
            $parts = explode(' ', trim($lead->name), 2);
            Contact::create([
                'user_id' => $user->id,
                'companie_id' => $customer->id,
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? '',
                'email' => $lead->email,
                'phone' => $lead->phone,
            ]);
            $lead->forceFill(['converted_company_id' => $customer->id])->save();
            $this->activity($user, $lead, 'converted', 'Lead converted to customer #'.$customer->id);

            return $customer;
        });
    }

    private function activity(User $user, Lead $lead, string $type, string $description): void
    {
        CrmActivity::create([
            'user_id' => $user->id,
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'type' => $type,
            'description' => $description,
        ]);
    }
}
