<?php

namespace App\Http\Resources;

use App\Models\Joey;
use Illuminate\Http\Resources\Json\JsonResource;

class joeyBasicSeenCategoryResource extends JsonResource
{


    public function __construct($resource)
    {

        parent::__construct($resource);
        $this->resource = $resource;

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $joeyName = '';

        foreach ($this->isPassed as $is_passed) {

            if (isset($is_passed->joey_id)) {
                $joeyName = Joey::where('id', $is_passed->joey_id)->first();
            }
            return [

                'question_id' => $is_passed->id ?? '',
                'name' => $joeyName->display_name ?? '',
                'is_passed' => $is_passed->is_passed ?? '',
            ];
        }

//        if (isset($this->isPassed->joey_id)) {
//                $joeyName = Joey::where('id', $this->isPassed->joey_id)->first();
//            }
//            return [
//
//                //'question_id' => $this->isPassed->id ?? '',
//               // 'name' => $joeyName->display_name ?? '',
//                'isPassedData' => $this->isPassed->toArray(),
//                //'is_passed' => $this->isPassed->is_passed ?? '',
//            ];
    }
}
