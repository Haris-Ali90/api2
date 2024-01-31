<?php

namespace App\Http\Requests\Api;

class MarkedRouteCompleteCronRequest extends Request
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

    public function messages()
    {
        return [
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $request_data = $this->all();


        // creating required rules
        $rules = [
            'date' => 'date',
            'page' => 'required|numeric',
            'limit'=>'required|numeric',
        ];

        return $rules;
    }

}
