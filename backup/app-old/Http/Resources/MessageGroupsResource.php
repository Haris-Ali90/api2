<?php

namespace App\Http\Resources;
use App\Models\Dashboard;
use App\Models\Joey;
use App\Models\JoeycoUsers;
use App\Models\Onboarding;
use App\Models\Thread;
use App\Models\Vendor;
use Carbon\Carbon;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageGroupsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $sender = null;
        if (isset($this->creator_type)) {
            if ($this->creator_type == Thread::Onboarding_Type)
            {
                $sender = Onboarding::find($this->user_id);
            }
            elseif ($this->creator_type == Thread::Joey_Type)
            {
                $sender = Joey::find($this->user_id);
            }
            elseif ($this->creator_type == Thread::Dashboard_Type || $this->creator_type == "Routing")
            {
                $sender = Dashboard::find($this->user_id);
            }
            elseif ($this->creator_type == Thread::Merchant_Type)
            {
                $sender = Vendor::find($this->user_id);
            }
            elseif ($this->creator_type == Thread::Guest_Type)
            {
                $sender = JoeycoUsers::find($this->user_id);
            }
        }
        // else
        // {
        //     $sender=null;
        // }

        return [
            'id' => isset($this->id) ? $this->id : 'N/A',
            'group_name'=> isset($this->name) ? $this->name : 'N/A',
            'group_creator'=>new SenderResource($sender),
            'group_creator_id'=> isset($this->user_id) ? $this->user_id : 'N/A',
            'creator_type'=> isset($this->creator_type) ? $this->creator_type : 'N/A',
            'message_group_pic'=> isset($this->message_group_pic) ? $this->message_group_pic : 'N/A',
            'created_at' => isset($this->created_at) ? Carbon::parse($this->created_at)->format('Y/m/d - H:i:s') : 'N/A',
            "messages"=> isset($this->messages) ? MessageListRessource::collection($this->messages) : 'N/A'
        ];
    }
}
