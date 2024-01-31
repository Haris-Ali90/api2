<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagerJoeyResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource)
    {

        parent::__construct($resource);
//        if(empty($_token)) {
//            $this->_token = request()->bearerToken();
//        }
//         else {
//             $this->_token = $_token;
//         }
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $joey_name= '';
        if (isset($this->first_name))
        {

            $joey_name =  $this->first_name.' '.$this->last_name;
        }

        return [
            'joey_id' => $this->id??'',
            'joey_name' => $joey_name,
            /*'email' => $this->email??'',
            'address' => $this->address??'',*/
            'phone' => $this->phone??'',
        ];
    }
}
