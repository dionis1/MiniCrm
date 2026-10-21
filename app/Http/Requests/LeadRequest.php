<?php

namespace App\Http\Requests;

use App\Support\CrmRules;
use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return CrmRules::lead(! $this->isMethod('post'));
    }
}
