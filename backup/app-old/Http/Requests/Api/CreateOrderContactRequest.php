<?php

namespace App\Http\Requests\Api;

class CreateOrderContactRequest extends Request
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
            'name'      => 'required',
            'email'   => 'nullable|email',
            'phone'      => 'nullable|numeric|digits:10',
        ];
    }

}
