<?php

namespace App\Http\Requests;

use App\Enums\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'stage' => ['nullable', Rule::enum(LeadStage::class)], 'sort' => ['nullable', Rule::in(['created_at', 'name', 'deal_value'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'per_page' => ['nullable', 'integer', 'between:1,100'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
