<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['filter' => ['nullable', Rule::in(['all', 'today', 'overdue', 'open', 'completed'])], 'priority' => ['nullable', Rule::enum(TaskPriority::class)], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
