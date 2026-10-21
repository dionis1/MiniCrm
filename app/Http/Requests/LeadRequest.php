<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $updating = $this->isMethod('put') || $this->isMethod('patch');
        $required = $updating ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:160'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'stage' => [$required, Rule::in(Lead::STAGES)],
            'deal_value' => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
            'probability' => ['sometimes', 'integer', 'between:0,100'],
            'version' => [$updating ? 'required' : 'prohibited', 'integer', 'min:0'],
        ];
    }
}
