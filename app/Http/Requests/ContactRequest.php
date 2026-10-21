<?php

namespace App\Http\Requests;

use App\Support\CrmRules;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return CrmRules::contact(! $this->isMethod('post'));
    }
}
