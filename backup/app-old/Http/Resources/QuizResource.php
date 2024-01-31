<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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


        return [
            'id' => $this->id,
            'question' => $this->question,
			'quiz_answer_id' => $this->correct_answer_id,
            'question_image' => $this->image,
            'answer' => QuizAnswersResource::collection($this->answers)


        ];
    }
}
