<?php

namespace App\Http\Requests\Api;

class ScheduleRequest extends Request
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
            'zone_id'      => 'required|numeric',
            'start'   => 'required|date',
            'end' => 'required|date',
            'vehicle_id' => 'required'
        ];
    }

}
