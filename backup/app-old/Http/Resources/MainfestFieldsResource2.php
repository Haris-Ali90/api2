<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class MainfestFieldsResource2 extends JsonResource
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
            'address1' =>$this->Location->address??'' ,
            'address2' => $this->merchantIds->address_line2??'' ,
            // 'address3' => $this->consigneeAddressLine3 ,
            'phone' => $this->sprintContact->phone??'',
           // 'zip' =>$this->consigneeAddressZip ,
            'city' => $this->Location->city->name??'' ,
        
        ];
    }
}
