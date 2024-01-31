<?php

namespace App\Http\Requests\Api;

class CreateOrderOtherRequest extends Request
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
            'notification_method'      => 'required',
            'confirm_pin'      => 'nullable|boolean',
            'confirm_image'   => 'nullable|boolean',
            'confirm_signature'   => 'nullable|boolean',
            'confirm_seal'   => 'nullable|boolean',
            'copy'=>'nullable',
        ];
    }

}
