<?php

namespace App\Http\Resources;

use App\Models\JoeycoUsers;
use App\Models\Thread;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Message;
use App\Models\Joey;
use App\Models\Onboarding;
use App\Models\Dashboard;
class ChatUnseenMessageResource extends JsonResource
{
    private $_token = '';

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
        return [
            'thread_id' => $this->id,
            'participants'=>new SenderResource($sender),
            'chat_unseen_message_count'=>$this->chat_unseen_message_count,
            'Messages'=>MessageListRessource::collection($this->chatUnseenMessage->where('sender_id','!=',$request->user_id)->where('creator_type','!=',$request->user_type))];
    }
}
