<?php

namespace App\Http\Requests\Api;

class CreateOrderLocationRequest extends Request
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
            'address'   => 'required',
            // 'postal_code'      => 'required|regex:/^\S*$/u',
            'postal_code'      => 'required',
            // 'postal_code'      => 'required|regex:/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z][ -]?\d[ABCEGHJ-NPRSTV-Z]\d$/i',
            // 'postal_code'      => 'required|regex:/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/',
            'address_line2'   => 'nullable',
            'pickup_buzzer'      => 'nullable',
            'buzzer'   => 'nullable',
        ];
    }

}
