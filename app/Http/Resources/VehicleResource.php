<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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

        return [
            'id' => $this->id?$this->id:null,
            'name'=> $this->name??'',

        ];
    }
}
