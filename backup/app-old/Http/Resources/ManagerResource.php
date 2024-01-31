<?php

namespace App\Http\Resources;


use App\Models\FinanceVendorCity;
use App\Models\JoeyDocument;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\AgreementUser;
use App\Models\OrderCategory;



class ManagerResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource, $_token = '')
    {

        parent::__construct($resource);

        if(empty($_token)) {
            $this->_token = request()->bearerToken();
        }
         else {
             $this->_token = $_token;
         }
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $hubs = explode(',',$this->statistics);
        $hub_city_name = [];
        foreach ($hubs as $hub)
        {
            $name = FinanceVendorCity::where('id',$hub)->first();
            if(isset($name->city_name)){
                $hub_city_name[] = $name->city_name;
            }

        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image'=>$this->profile_picture??'',
            'role' => ($this->role)? $this->role->display_name : 'N/A',
            'hub_city_id' => $this->statistics,
            'hub_city_name' => implode(',',$hub_city_name),
            'token'=>$this->_token,
        ];
    }
}

