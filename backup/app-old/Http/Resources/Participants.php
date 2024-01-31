<?php

namespace App\Http\Resources;
use App\Models\Joey;
use App\Models\Onboarding;
use App\Models\Participants as messageparticipants;
use Illuminate\Http\Resources\Json\JsonResource;

class Participants extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {   
        $participants = messageparticipants::where('thread_id', $request->thread_id)->first();
        if($participants)
        {
        if($participants->creator_type=='onboarding')
        {
           
            $sender=Onboarding::find($participants->user_id);
           
        }
        elseif($participants->creator_type=='Joey')
        {
            $sender=Joey::find($participants->user_id);
        }
        elseif($this->other_user_type=='Dashboard' || $this->other_user_type=="Routing")
        {
           
            $sender=Dashboard::find($this->other_user_id);
        }
            return [
                'id' => $sender->id,
                'user_type'=> $participants->creator_type,
                'first_name'=> $sender->first_name,
                'last_name'=>$sender->last_name
            ];
        }
        else
        {
            return [];

        }
      
        
    }
}
