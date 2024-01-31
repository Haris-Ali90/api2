<?php

namespace App\Http\Resources;

use App\Models\Onboarding;
use App\Models\Joey;
use App\Models\Dashboard;
use Carbon\Carbon;

use Illuminate\Http\Resources\Json\JsonResource;

class ThreadUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->creator_type=='onboarding')
        {
            $sender=Onboarding::find($this->user_id);
        }
        elseif($this->creator_type=='Joey')
        {
            $sender=Joey::find($this->user_id);
        }
        elseif($this->other_user_type=='Dashboard' || $this->other_user_type=="Routing")
        {
           
            $sender=Dashboard::find($this->other_user_id);
        }
        return [
            'user'=>new SenderResource($sender),
            'creator_type'=>$this->creator_type,
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s'),
        ];
    }
}
