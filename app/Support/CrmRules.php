<?php

namespace App\Support;

use App\Enums\CustomerStatus;
use App\Enums\LeadStage;
use App\Enums\TaskPriority;
use App\Models\User;
use Illuminate\Validation\Rule;

final class CrmRules
{
    public static function lead(bool $updating = false): array
    {
        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:160'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'stage' => [$updating ? 'sometimes' : 'required', Rule::enum(LeadStage::class)],
            'deal_value' => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
            'probability' => ['sometimes', 'integer', 'between:0,100'],
            'version' => [$updating ? 'required' : 'prohibited', 'integer', 'min:0'],
        ];
    }

    public static function customer(bool $updating = false): array
    {
        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:160'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'website_url' => ['sometimes', 'nullable', 'url:http,https', 'max:255'],
            'status' => ['sometimes', Rule::enum(CustomerStatus::class)],
            'tags' => ['sometimes', 'array', 'max:20'], 'tags.*' => ['string', 'max:40', 'distinct'],
        ];
    }

    public static function contact(bool $updating = false): array
    {
        return [
            'first_name' => [$updating ? 'sometimes' : 'required', 'string', 'max:120'],
            'last_name' => [$updating ? 'sometimes' : 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:20'], 'tags.*' => ['string', 'max:40', 'distinct'],
        ];
    }

    public static function task(User $actor, bool $updating = false): array
    {
        return [
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'lead_id' => ['sometimes', 'nullable', 'integer', Rule::exists('leads', 'id')->where('user_id', $actor->id)],
            'company_id' => ['sometimes', 'nullable', 'integer', Rule::exists('companies', 'id')->where('user_id', $actor->id)],
        ];
    }
}
