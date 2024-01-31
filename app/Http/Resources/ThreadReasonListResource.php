<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class ThreadReasonListResource extends JsonResource
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
            'thread_reason_list_id'=> $this->thread_reason_list_id,
            'reason'=>$this->reason,
            'department'=>$this->department,
            'thread_reason'=>ThreadReasonListResource::collection($this->threadReason),
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s'),

        ];
    }
}
