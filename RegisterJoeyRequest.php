<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterJoeyRequest extends FormRequest
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

    public function messages()
    {
        return [

                'phone.required' => 'Please enter the correct phone-number',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'first_name' => 'required|string|max:50|regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/',
            'last_name'  => 'required|string|max:50|regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/',
            'email'      => 'required',

/*            'unit_number'      => 'required',*/


        ];
    }
}
