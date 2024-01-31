<?php

namespace App\Http\Requests\Api;

class StoreDocumentRequest extends Request
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
            'driver_permit'=>'mimes:jpg,jpeg,png|max:5120',
            'driver_license'=>'mimes:jpg,jpeg,png|max:5120',
            'study_permit'=>'mimes:jpg,jpeg,png|max:5120',
            'vehicle_insurance'=>'mimes:jpg,jpeg,png|max:5120',
            'additional_document_1'=>'mimes:jpg,jpeg,png|max:5120',
            'additional_document_2'=>'mimes:jpg,jpeg,png|max:5120',
            'additional_document_3'=>'mimes:jpg,jpeg,png|max:5120',
            'sin'=>'string',
            'driver_permit_exp_date' => 'date_format:Y-m-d',
            'driver_license_exp_date' => 'date_format:Y-m-d',
            'study_permit_exp_date' => 'date_format:Y-m-d',
            'vehicle_insurance_exp_date' => 'date_format:Y-m-d',
            'additional_document_1_exp_date' => 'date_format:Y-m-d',
            'additional_document_2_exp_date' => 'date_format:Y-m-d',
            'additional_document_3_exp_date' => 'date_format:Y-m-d',
            'sin_exp_date' => 'date_format:Y-m-d',

        ];
    }

}
