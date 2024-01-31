<?php

namespace App\Http\Requests\Api;

class ClaimResubmitRequest extends Request
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
    // public function messages()
    // {
    //     // return [
    //     //     'order_no.exists' =>'This order no. is invalid',
    //     // ];
    // }

    public function rules()
    {
        return [
            'tracking_id_merchant_order_no' => 'required|exists:claims,tracking_id',
            'reason_id'   => 'required|exists:claim_reasons,id,slug,Re-Submitted',
//            'image' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'tracking_id_merchant_order_no.exists' => 'Invalid Tracking id/Merchant order no.',
        ];
    }

}
