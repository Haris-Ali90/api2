<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class JoeyDepositResource extends JsonResource
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
            'id' => $this->id,
            'institution_number'=>$this->deposit->institution_no??'',
            'branch_number'=>$this->deposit->branch_no??'',
            'account_number' => $this->deposit->account_no??'',
            'hst_number'=> $this->hst_number??'',
            'hear_from' => $this->hear_from??'',
            'image'=>$this->deposit->image??'',
            'hst_company'=>$this->hst_company??''
        ];
    }
}
