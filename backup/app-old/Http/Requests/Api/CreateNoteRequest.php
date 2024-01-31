<?php

namespace App\Http\Requests\Api;

class CreateNoteRequest extends Request
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
            'tracking_id'   => 'required|exists:merchantids,tracking_id',
            'note'      => 'required',
        ];
    }

}
