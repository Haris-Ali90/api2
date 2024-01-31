<?php

namespace App\Models;
use App\Models\Interfaces\JoeyAttemptQuizInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyAttemptQuiz extends Model implements JoeyAttemptQuizInterface
{

    public $table = 'joey_attempted_quiz_details';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'joey_id',
        'question_id',
        'answer_id',
        'is_correct',
        'quiz_id'


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
    return $this->belongsTo(QuizAnswer::class,'answer_id','id');
        }
    public function question(){

        return $this->belongsTo(QuizQuestion::class,'question_id','id');
    }

    public function joey(){

        return $this->belongsTo(User::class,'joey_id','id');
    }

    public function quiz(){
        return $this->belongsTo(JoeyQuiz::class,'quiz_id','id');
    }


}
