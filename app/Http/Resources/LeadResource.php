<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'stage' => $this->stage,
            'deal_value' => $this->deal_value,
            'probability' => $this->probability,
            'expected_value' => round((float) $this->deal_value * $this->probability / 100, 2),
            'version' => $this->version,
            'converted_customer_id' => $this->converted_company_id,
            'converted' => $this->converted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
