<?php

namespace App\Http\Requests\Api;

class CreateOrderPaymentRequest extends Request
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
            'type'   => 'present|nullable|in:make,collect',
            'amount'=> 'present|nullable|numeric|gt:0',
        ];
    }

}
