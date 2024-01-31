<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'created_at'=> $this->created_at->toDateTimeString(),
            'notification_type' => $this->notification_type,
            'notification_data' => [
                'title'=>$this->notification,
                'message'=>$this->notification_data['body']??'',
            ]
        ];
    }
}
