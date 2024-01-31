<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class MainfestFieldsResource1 extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);


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


            'address1' =>$this->consigneeAddressLine1??'' ,
            'address2' => $this->consigneeAddressLine2 ??'',
            'address3' => $this->consigneeAddressLine3 ??'',
            'phone' => $this->consigneeAddressContactPhone??'',
            'zip' =>$this->consigneeAddressZip ??'',
            'city' => $this->shipFromAddressZip ??'',
        
        ];
    }
}
