<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['type' => ['required', Rule::in(['call', 'note', 'email', 'meeting'])], 'description' => ['required', 'string', 'max:3000']];
    }
}
