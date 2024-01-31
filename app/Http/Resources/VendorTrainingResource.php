<?php

namespace App\Http\Resources;

use App\Models\JoeyTrainingSeen;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorTrainingResource extends JsonResource
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

        if($this->type=='video'){
            $seenCount = JoeyTrainingSeen::where('joey_id',auth()->user()->id)->where('training_id',$this->id)->first();
            if ($seenCount){
                $seen = 1;
            }
            else{
                $seen = 0;
            }
        }else{
            $seenCount = JoeyTrainingSeen::where('joey_id',auth()->user()->id)->where('training_id',$this->id)->first();
            if ($seenCount){
                $seen = 1;
            }
            else{
                $seen = 0;
            }

        }

        return [
            'id' => $this->id??'',
            'order_category_id'=> $this->order_category_id??null,
            'vendor_id'=> $this->vendors_id??null,
            'name'=> $this->name??'',
            'description'=> $this->description??'',
            'type'=> $this->type??'',
             'url'=> $this->url??'',
             'extension'=> $this->extension??'',
             'seen'=> $seen??'',
             'mandatory' => $this->is_compulsory,
             'duration' => $this->duration??'00:00:00',
             'thumbnail' => $this->thumbnail 

        ];
    }
}
