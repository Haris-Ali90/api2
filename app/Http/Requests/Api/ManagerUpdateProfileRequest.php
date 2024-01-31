<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ManagerUpdateProfileRequest extends FormRequest
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
            'full_name' => 'required|string|max:50|regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/',
            'phone' => 'required|min:10|max:24',
//            'profile_picture' => 'image|mimes:jpeg,png,jpg|max:5000'
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Phone No. is required',
//            'profile_picture.image'    => "The profile picture must be an image",
//            'profile_picture.mimes'       => 'The profile picture must be a file of type: jpeg, png, jpg',
//            'profile_picture.max'      => 'The profile picture may not be greater than 5000 kilobytes',
        ];
    }
}
