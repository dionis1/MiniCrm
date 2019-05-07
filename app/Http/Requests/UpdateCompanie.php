<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanie extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           'name'=>'required|alpha',
           'email'=>'nullable|email',
           'logo'=>'nullable|dimensions:min_width=100,min_height=100|mimes:jpeg,bmp,png,jpg',
           'website_url'=>'nullable'
        ];
    }
}
