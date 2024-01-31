<?php

namespace App\Models;
use App\Models\Interfaces\QuizQuestionInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model implements QuizQuestionInterface
{

    public $table = 'quiz_questions';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'order_category_id',
        'form_id',
        'question',
        'correct_answer_id',
        'vendor_id',

    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */


    public function answers(){

        return $this->hasMany(QuizAnswer::class,'quiz_questions_id','id');
    }

    public function categories(){

        return $this->BelongsTo(OrderCategory::class,'id','order_category_id')->where('type','=','basic');
    }

    public function isPassed(){

        return $this->hasMany(JoeyQuiz::class,'id','id')->where('is_passed',1)->where('joey_id',auth()->user()->id);
    }
}
