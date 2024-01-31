<?php

namespace App\Http\Resources;
use App\Models\SprintTasks;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyOrderListResource extends JsonResource
{

    private $duration;

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
        $date=convertTimeZone($this->earning->created_at->format('Y-m-d H:i:s'),'UTC',$this->convert_to_timezone,'Y-m-d');
        $sprintId = explode('-', $this->earning->reference);

        return [
            'id' => (int)$sprintId[1],
            'order_num' => 'CR-'.$sprintId[1]??'',
            'date' => $date??'',
            'credit_amount' => (float)$this->earning->amount??'',
            'date_time' => $this->earning->created_at->format('Y-m-d H:i:s')??'',
        ];
    }
}
