<?php

namespace App\Http\Resources;

use App\Models\Thread;
use Illuminate\Http\Resources\Json\JsonResource;

class SenderResource extends JsonResource
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
        $userType = null;
        if($this->getTable()== 'onboarding_users')
        {

            $userType =  Thread::Onboarding_Type;

        }
        elseif($this->getTable() == 'joeys')
        {

            $userType = Thread::Joey_Type;

        }
        elseif($this->getTable() == 'dashboard_users') {

            $userType = Thread::Dashboard_Type;

        }
        elseif ($this->getTable() == 'vendors')
        {
            $userType = Thread::Merchant_Type;
        }
        elseif ($this->getTable() == 'joeyco_user')
        {
            $userType = Thread::Guest_Type;
        }

        return [
            'id' => $this->id,
            'first_name' => isset($this->first_name) ? $this->first_name : null,
            'last_name'=>  isset($this->last_name) ? $this->last_name : null,
            'full_name'=>  isset($this->full_name) ? $this->full_name : null,
            'this' => $userType, //isset($this->userType) ? $this->userType : $this->type
        ];
    }
}
