<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class JoeySignUpRequest extends FormRequest
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

            'first_name'        => 'required|regex:/^[\pL\s\-]+$/u|max:32',
            'last_name'        => 'required|regex:/^[\pL\s\-]+$/u|max:32',
            'email'             => 'required|email|unique:joeys,email,NULL,id,deleted_at,NULL',
            'password'          => 'required|min:6|max:30',
            'device_token'          => 'required',
            'device_type'          => 'required',

            
        ];
    }
}
