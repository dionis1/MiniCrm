<?php

namespace App\Http\Requests;

use App\Support\CrmRules;
use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return CrmRules::task($this->user(), ! $this->isMethod('post'));
    }
}
