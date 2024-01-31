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

class GroupMessageDetailResource extends JsonResource
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

            if ($this->receiver_type == Thread::Onboarding_Type)
            {
                $sender = Onboarding::find($this->receiver_id);
            }
            elseif ($this->receiver_type == Thread::Joey_Type)
            {
                $sender = Joey::find($this->receiver_id);
            }
            elseif ($this->receiver_type == Thread::Dashboard_Type || $this->creator_type == "Routing")
            {
                $sender = Dashboard::find($this->receiver_id);
            }
            elseif ($this->receiver_type == Thread::Merchant_Type)
            {
                $sender = Vendor::find($this->receiver_id);
            }
            elseif ($this->receiver_type == Thread::Guest_Type)
            {
                $sender = JoeycoUsers::find($this->receiver_id);
            }

        return [
            'id' => $this->id,
            'user'=>new SenderResource($sender),
            'creator_type'=>$this->receiver_type,
            'seen_status'=>$this->seen_at,
            'deliver_status'=>$this->deliver_at,
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s')
        ];
    }
}
