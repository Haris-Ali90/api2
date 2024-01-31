<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class JoeyRouteResource extends JsonResource
{
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

        $pickedUpCount=0;
        if(!empty($this->sprintTask)) {
            if (!empty($this->sprintTask->sprint_id)) {
/*                dd($this->sprintTask->sprint_id);*/
                $pickedUp = SprintTaskHistory::where('sprint_id', '=', $this->sprintTask->sprint_id)
//                    ->groupBy('sprint_id')
//                    ->where('status_id', '=', '121')
                    ->orderBy('id', 'DESC')
                    ->first();
                if($pickedUp->status_id == 121){
                    $pickedUpCount=1;
                }

            }


        }

        $returned=0;

        if(isset($this->sprintTask->status_id)) {


            if (in_array($this->sprintTask->status_id, [101,110,155,104, 105, 106, 107, 108, 109, 111, 131, 135])) {
                $returned = 1;
            }
        }


        $latitude='';
        $longitude='';

        if(isset($this->sprintTask->Location)){
          $lat[0] = substr($this->sprintTask->Location->latitude, 0, 2);
          $lat[1] = substr($this->sprintTask->Location->latitude, 2);
          $latitude = $lat[0].".".$lat[1];

          $long[0] = substr($this->sprintTask->Location->longitude, 0, 3);
          $long[1] = substr($this->sprintTask->Location->longitude, 3);
          $longitude = $long[0].".".$long[1];

        }

        $notes=[];
        $notesArray=[];
        if($this->sprintTask){
            if($this->sprintTask->merchantIds){
                $notesArray=$this->sprintTask->merchantIds->notes;
            }
        }
        if(count($notesArray) > 0){
            $i=0;
            foreach ($notesArray as $note) {
                $notes[$i]['tracking_id']=$note->tracking_id??'';
                $notes[$i]['note']=$note->note??'';
                $notes[$i]['type']=$note->type??'';
                $i++;
            }
        }

        return [
     'num' => 'R-'.$this->route_id.'-'.$this->ordinal ,
        'start_time' => $this->sprintTask->merchantIds->start_time??'',

            'end_time' => $this->sprintTask->merchantIds->end_time??'',
            'arrival_time' => $this->arrival_time ??'',
            'finish_time' => $this->finish_time??'',
            'contact' => [
                'name' => $this->sprintTask->sprintContact->name??'',
                'phone' => $this->sprintTask->sprintContact->phone??'',
                 'email' => $this->sprintTask->sprintContact->email??''
           ]
           ,
             'location' =>[
               'address' => $this->sprintTask->Location->address??'',
                 'latitude' =>  $latitude??'',
                'longitude' =>  $longitude??'',
              'address_line2' => $this->sprintTask->merchantIds->address_line2??''
             ],
            'task_id' =>$this->task_id??'',
            'tracking_id' => $this->sprintTask->merchantIds->tracking_id??'',
            'merchant_order_num' => $this->sprintTask->merchantIds->merchant_order_num??'',
            'ordinal' =>$this->ordinal??'',
            'has_picked'=> $pickedUpCount,
            'returned' =>$returned??'',
            'notes'=>$notes
        ];
    }
}
