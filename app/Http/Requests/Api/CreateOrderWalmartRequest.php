<?php

namespace App\Http\Requests\Api;

class CreateOrderWalmartRequest extends Request
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
            'store_id'=> 'required|numeric',
            'due_time'   => 'required|numeric',
            'vehicle_id'=> 'required|exists:vehicles,id',
            'merchant_order_num'=> 'required|unique:merchantids,merchant_order_num',
            'tracking_id'=> 'nullable',
            'start_time'=> 'nullable|required_with:end_time|date_format:H:i',
            'end_time'  => 'nullable|date_format:H:i|after:start_time',
            'tip'=> 'nullable|numeric|gt:0',
        ];
    }

}
