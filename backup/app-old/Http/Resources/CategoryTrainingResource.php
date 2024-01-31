<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


use App\Models\JoeyAttemptQuiz;
use App\Models\JoeyQuiz;
use App\Models\QuizQuestion;

class CategoryTrainingResource extends JsonResource
{


    public function __construct($resource)
    {

        parent::__construct($resource);
        $this->resource=$resource;

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $joey_id=auth()->user()->id;
        $timesWatched=0;
        $has_watch=0;
        $compulsory_count=count($this->getcumpulsorytrainings);
        if(!empty($this->singleTrainingAgainstCategoryId)){

            // foreach ($this->singleTrainingAgainstCategoryId->trainingSeen as $record){
            foreach ($this->mulitpleTrainingAgainstCategoryId as $records){

                  foreach ($records->trainingSeen as $record){
                    // if($record->joey_id == $joey_id )
                        if($record->checkIfCompulsory->is_compulsory==1 && $record->joey_id == $joey_id){
                                    $timesWatched=$timesWatched+1;
                        }
                }
            }

        }

        // if($timesWatched>0){
        //     $has_watch=1;
        // }
        if($timesWatched >= $compulsory_count){
            $has_watch=1;
        }

        $passed=JoeyQuiz::where('category_id',$this->id)->where('joey_id',$joey_id)->where('is_passed',1)->get();
        
        $is_passed=0;
        if($passed->count()>0){
            $is_passed=1;
        }

        return [
            'category_id'=>$this->id??'',
            'category_name'=>ucfirst($this->name)??'',
            'order_count'=>($this->order_count)?$this->order_count:0,
            'training_id'=>$this->singleTrainingAgainstCategoryId->id??'',
            'training_count'=>$this->mulitpleTrainingAgainstCategoryId->count()??0,
            'has_watch'=>$has_watch??0,
            'is_passed'=>$is_passed??0,


        ];
    }
}
