<?php

namespace App\Models;
use App\Models\Interfaces\JoeyAttemptQuizInterface;
use App\Models\Interfaces\JoeyQuizInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyQuiz extends Model implements JoeyQuizInterface
{

    public $table = 'joey_attempted_quiz';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'category_id',
        'joey_id',
        'is_passed'
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



    public function quizRecord(){
        return $this->belongsTo(QuizQuestion::class,'id','id');
    }
    public function orderCategory(){
        return $this->belongsTo(OrderCategory::class,'category_id','id');
    }



}
