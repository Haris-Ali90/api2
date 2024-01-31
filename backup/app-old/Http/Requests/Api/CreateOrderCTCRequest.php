<?php

namespace App\Http\Requests\Api;

class CreateOrderCTCRequest extends Request
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
            'creator_id'=> 'required|exists:vendors,id',
            'due_time'   => 'required|numeric',
            'vehicle_id'=> 'required|exists:vehicles,id',
            'tracking_id'=> 'required|unique:merchantids,tracking_id',
            'merchant_order_num' => 'nullable',
            'start_time'=> 'nullable|required_with:end_time|date_format:H:i',
            'end_time'  => 'nullable|date_format:H:i|after:start_time',
            'tip'=> 'nullable|numeric|gt:0',
        ];
    }

}
