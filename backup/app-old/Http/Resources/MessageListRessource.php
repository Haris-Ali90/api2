<?php

namespace App\Http\Resources;
use App\Models\Dashboard;
use App\Models\Joey;
use App\Models\JoeycoUsers;
use App\Models\Onboarding;
use App\Models\Thread;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageListRessource extends JsonResource
{
    private $_token = '';

    public function __construct($resource)
    {

        parent::__construct($resource);
//        if(empty($_token)) {
//            $this->_token = request()->bearerToken();
//        }
//         else {
//             $this->_token = $_token;
//         }
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
        if($this->other_user_type == Thread::Onboarding_Type || $this->creator_type == Thread::Onboarding_Type)
        {
            if (isset($this->sender_id))
            {
                $sender=Onboarding::find($this->sender_id);
            }
            else
            {
                $sender=Onboarding::find($this->other_user_id);
            }


        }
        elseif($this->other_user_type == Thread::Joey_Type || $this->creator_type == Thread::Joey_Type)
        {
            if (isset($this->sender_id))
            {
                $sender=Joey::find($this->sender_id);
            }
            else
            {
                $sender=Joey::find($this->other_user_id);
            }


        }
        elseif($this->other_user_type == Thread::Dashboard_Type || $this->creator_type == Thread::Dashboard_Type)
        {
            if (isset($this->sender_id))
            {
                $sender = Dashboard::find($this->sender_id);
            }
            else
            {
                $sender = Dashboard::find($this->other_user_id);
            }


        }
        elseif($this->other_user_type == "Routing" || $this->creator_type == "Routing")
        {

            if (isset($this->sender_id))
            {
                $sender = Dashboard::find($this->sender_id);
            }
            else
            {
                $sender = Dashboard::find($this->other_user_id);
            }


        }
        elseif ($this->other_user_type == Thread::Merchant_Type || $this->creator_type == Thread::Merchant_Type)
        {
            if (isset($this->sender_id))
            {
                $sender = JoeycoUsers::find($this->sender_id);
            }
            else
            {
                $sender = JoeycoUsers::find($this->other_user_id);
            }

        }
        elseif ($this->other_user_type == Thread::Guest_Type || $this->creator_type == Thread::Guest_Type)
        {
            if (isset($this->sender_id))
            {
                $sender = JoeycoUsers::find($this->sender_id);
            }
            else
            {
                $sender = JoeycoUsers::find($this->other_user_id);
            }

        }

        return [
            'message_id' => $this->id,
            'message'=> $this->body,
            'sender'=>new SenderResource($sender),
            'sender_id'=>$this->sender_id,
            'message_type'=>$this->message_type,
            'creator_type'=>$this->creator_type,
            'is_read'=>$this->is_read,
            'files'=>MessageFileResource::collection($this->messageFile),
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s'),
        ];
    }
}
