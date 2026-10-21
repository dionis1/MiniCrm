<?php

namespace App\Http\Requests;

use App\Enums\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['stage' => ['required', Rule::enum(LeadStage::class)], 'version' => ['required', 'integer', 'min:0']];
    }
}
