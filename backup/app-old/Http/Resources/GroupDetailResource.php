<?php

namespace App\Http\Resources;
use App\Models\Joey;
use App\Models\Onboarding;
use Carbon\Carbon;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupDetailResource extends JsonResource
{
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
            'group_name'=>$this->name,
            'message_group_pic'=>$this->message_group_pic,
            'messages'=>$this->messages,
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s')
        ];
    }
}
