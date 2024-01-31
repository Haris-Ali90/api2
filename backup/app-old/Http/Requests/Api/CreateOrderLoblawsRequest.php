<?php

namespace App\Http\Requests\Api;

class CreateOrderLoblawsRequest extends Request
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
        $datetime=time() + (0.5*60*60); //adding 30 mins

        return [
            'creator_id'=> 'required|exists:vendors,id',
            'due_time'   => 'required|numeric|gte:'.$datetime,
            'vehicle_id'=> 'required|exists:vehicles,id',
            'merchant_order_num'=> 'required|unique:merchantids,merchant_order_num',
            'tracking_id'=> 'nullable',
            //'merchant_order_num' => 'nullable|unique:merchantids,merchant_order_num',
            'start_time'=> 'nullable|required_with:end_time|date_format:H:i',
            'end_time'  => 'nullable|date_format:H:i|after:start_time',
            'tip'=> 'nullable|numeric|gt:0',
            // 'sprint_creator_id'=> 'required|exists:vendors,id',
            // 'sprint_duetime'   => 'required|numeric|gte:'.$datetime,
            // 'sprint_vehicle_id'=> 'required|exists:vehicles,id',
            // 'sprint_tracking_id'=> 'nullable|unique:merchantids,tracking_id',
            // 'sprint_merchant_order_num' => 'nullable|unique:merchantids,merchant_order_num',
            // 'sprint_start_time'=> 'nullable|date_format:H:i',
            // 'sprint_end_time'  => 'nullable|date_format:H:i|after:sprint_start_time',
            // 'sprint_tip'=> 'nullable|numeric|gt:0',

            // 'contact_name'      => 'required',
            // 'contact_email'   => 'nullable|email',
            // 'contact_phone'      => 'nullable|numeric|digits:10',

            // 'location_address'   => 'required',
            // 'location_postal_code'      => 'required|regex:/^\S*$/u',
            // 'location_address_line2'   => 'nullable',
            // 'location_pickup_buzzer'      => 'nullable',
            // 'location_buzzer'   => 'nullable',

            // 'notification_method'      => 'required',
            // 'confirm_pin'      => 'nullable|boolean',
            // 'confirm_image'   => 'nullable|boolean',
            // 'confirm_signature'   => 'nullable|boolean',
            // 'confirm_seal'   => 'nullable|boolean',

            // 'payment_type'   => 'nullable|in:make,collect',
            // 'payment_amount'=> 'nullable|numeric|gt:0',

            // 'copy'=>'nullable',

        ];
    }

}
