<?php

namespace App\Http\Resources;
use App\Models\Joey;
use App\Models\JoeycoUsers;
use App\Models\Onboarding;
use App\Models\Thread;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Models\Dashboard;

class ThreadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $sender=null;

        if($this->other_user_type== Thread::Onboarding_Type)
        {

            $sender=Onboarding::find($this->other_user_id);

        }
        elseif($this->other_user_type==Thread::Joey_Type)
        {

            $sender=Joey::find($this->other_user_id);

        }
        elseif($this->other_user_type==Thread::Dashboard_Type || $this->other_user_type=="Routing") {

            $sender = Dashboard::find($this->other_user_id);

        }
        elseif ($this->other_user_type==Thread::Guest_Type)
        {
            $sender = JoeycoUsers::find($this->other_user_id);
        }
        
        if($request->user_type=='Joey')
        {
            $creator_type=$request->user_type;
            $user_id=$request->user_id;
            $other_user_type=$this->other_user_type;
            $other_user_id=$this->other_user_id;

        }
        else
        {
            $other_user_type =$this->creator_type;
            $other_user_id =$this->user_id;
            $creator_type=$this->other_user_type;
            $user_id=$this->other_user_id;
        }

        return [
            'id' => $this->id,
            'user_id' => $user_id,
			'creator_type' => $creator_type,
            'other_user_type'=>$other_user_type,
            'other_user_id'=>$other_user_id,
            'is_thread_end'=>$this->is_thread_end,
            'is_accepted'=>$this->is_accepted,
            'deleted_at'=>$this->deleted_at,
            "thread_reason"=>$this->threadReasonList,
            "department"=>$this->department,
            "thread_reason_parent"=>$this->threadReasonListParent(),
            'participants'=>new SenderResource($sender),
            'chat_unseen_message_count'=>$this->chat_unseen_message_count,
            'last_message'=>$this->lastchatMessage,
			'creator' => User::find($this->user_id, ['id', 'first_name', 'last_name', 'nickname', 'email', 'date_of_birth', 'phone', 'image', 'image_path']),
        ];
    }
}
