<?php

namespace App\Http\Resources;

use App\Models\JoeycoUsers;
use App\Models\Thread;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Message;
use App\Models\Participants;
use App\Models\Onboarding;
use App\Models\Joey;
use App\Models\Dashboard;

class ChatMessageResource extends JsonResource
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
            elseif ($this->other_user_type==Thread::Merchant_Type)
            {
                $sender = JoeycoUsers::find($this->other_user_id);
            }
            elseif ($this->other_user_type==Thread::Guest_Type)
            {
                $sender = JoeycoUsers::find($this->other_user_id);
            }

            $chatMessage = Message::where('thread_id',$this->id)
                // ->where('sender_id', $this->user_id)
                // ->where('creator_type', $this->creator_type)
                ->get();

        return [
            'thread_id' => $this->id,
            'user_id' => $this->user_id,
			'creator_type' => $this->creator_type,
            'is_thread_end'=>$this->is_thread_end,
            'is_accepted'=>$this->is_accepted,
            'deleted_at'=>$this->deleted_at,
            'participants'=>new SenderResource($sender),
            "thread_reason"=>$this->threadReasonList,
            "thread_reason_parent"=>$this->threadReasonListParent(),
            'Messages'=>MessageListRessource::collection($chatMessage)
        ];
    }
}
