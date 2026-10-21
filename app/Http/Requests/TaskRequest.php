<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $owner = $this->user()->id;

        return [
            'title' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'lead_id' => ['sometimes', 'nullable', Rule::exists('leads', 'id')->where('user_id', $owner)],
            'company_id' => ['sometimes', 'nullable', Rule::exists('companies', 'id')->where('user_id', $owner)],
        ];
    }
}
