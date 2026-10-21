<?php

namespace Database\Seeders;

use App\Actions\CompleteTaskAction;
use App\Actions\ConvertLeadAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateLeadAction;
use App\Actions\CreateTaskAction;
use App\Actions\LogCustomerActivityAction;
use App\Actions\SaveContactAction;
use App\Enums\LeadStage;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public const PASSWORD = 'MiniCrmDemo!2026';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo data is only available in local and testing environments.');
        }
        DB::transaction(function (): void {
            $sales = User::firstOrCreate(['email' => 'sales@demo.minicrm.test'], [
                'name' => 'Alex Morgan', 'password' => self::PASSWORD,
            ]);
            $other = User::firstOrCreate(['email' => 'other@demo.minicrm.test'], [
                'name' => 'Sam Rivera', 'password' => self::PASSWORD,
            ]);
            $companies = ['Northstar Studio', 'Evergreen Analytics', 'Harbor Design', 'Summit Learning', 'Atlas Research'];
            $people = ['Maya Chen', 'Leo Fischer', 'Sofia Martin', 'Noah Williams', 'Amira Hassan', 'Ethan Brooks', 'Isabel Costa', 'Oliver Kim', 'Emma Novak', 'Lucas Moreau'];
            $stages = LeadStage::cases();
            foreach ($people as $index => $name) {
                $email = 'lead-'.($index + 1).'@demo.minicrm.test';
                if (Lead::where('user_id', $sales->id)->where('email', $email)->exists()) {
                    continue;
                }
                $lead = app(CreateLeadAction::class)($sales, [
                    'name' => $name, 'company_name' => $index === 3 ? 'Beacon Labs' : $companies[$index % 5], 'email' => $email,
                    'stage' => $stages[$index % 5]->value, 'deal_value' => 2500 + $index * 1750,
                    'probability' => [15, 35, 65, 100, 0][$index % 5],
                ]);
                if ($index === 3) {
                    app(ConvertLeadAction::class)($sales, $lead, $lead->version);
                }
            }
            foreach ($companies as $index => $name) {
                $email = 'company-'.($index + 1).'@demo.minicrm.test';
                if (Customer::where('user_id', $sales->id)->where('email', $email)->exists()) {
                    continue;
                }
                $customer = app(CreateCustomerAction::class)($sales, [
                    'name' => $name, 'email' => $email, 'phone' => '+1 202 555 01'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'status' => $index === 4 ? 'inactive' : 'active', 'tags' => $index % 2 ? ['partner', 'growth'] : ['enterprise', 'priority'],
                ]);
                foreach (['Operations lead', 'Head of product'] as $position => $title) {
                    [$first, $last] = explode(' ', $people[$index * 2 + $position], 2);
                    app(SaveContactAction::class)($sales, $customer, ['first_name' => $first, 'last_name' => $last, 'position' => $title, 'email' => "contact-{$index}-{$position}@demo.minicrm.test", 'tags' => ['decision-maker']]);
                }
                app(LogCustomerActivityAction::class)($sales, $customer, [
                    'type' => 'meeting', 'description' => 'Discovery session completed. The team is reviewing a phased rollout and a follow-up proposal for next week.',
                ]);
            }
            $titles = ['Send Northstar proposal', 'Call Evergreen about renewal', 'Prepare Harbor discovery notes', 'Review Summit requirements', 'Schedule Atlas introduction', 'Confirm procurement timeline', 'Share the onboarding checklist', 'Follow up on the pilot', 'Review quarterly account plan', 'Send meeting recap', 'Prepare product walkthrough', 'Check contract feedback'];
            $customerId = Customer::where('user_id', $sales->id)->where('email', 'company-1@demo.minicrm.test')->value('id');
            foreach ($titles as $index => $title) {
                if (CrmTask::where('user_id', $sales->id)->where('title', $title)->exists()) {
                    continue;
                }
                $task = app(CreateTaskAction::class)($sales, [
                    'title' => $title, 'description' => 'Demo follow-up: confirm the next step and record the outcome with the customer.',
                    'priority' => ['high', 'normal', 'low'][$index % 3],
                    'due_at' => match ($index % 3) {
                        0 => now()->subDay()->toIso8601String(),
                        1 => today()->setTime(23, 30)->toIso8601String(),
                        2 => now()->addDays(3)->toIso8601String(),
                    },
                    'company_id' => $customerId,
                ]);
                if ($index >= 9) {
                    app(CompleteTaskAction::class)($sales, $task, true);
                }
            }
            if (! Lead::where('user_id', $other->id)->where('email', 'private@demo.minicrm.test')->exists()) {
                app(CreateLeadAction::class)($other, ['name' => 'Private workspace example', 'email' => 'private@demo.minicrm.test', 'stage' => 'proposal', 'deal_value' => 9000]);
            }
        });
    }
}
