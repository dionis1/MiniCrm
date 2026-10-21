<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'tag' => ['nullable', 'string', 'max:40'], 'status' => ['nullable', Rule::enum(CustomerStatus::class)], 'sort' => ['nullable', Rule::in(['name', 'created_at'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
