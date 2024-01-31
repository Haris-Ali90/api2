<?php

namespace App\Models;

use App\Models\Interfaces\JoeyTrainingSeenInterface;
use App\Models\Interfaces\TrainingInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyTrainingSeen extends Model implements JoeyTrainingSeenInterface
{

    public $table = 'joey_training_seen';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'joey_id',
        'training_id',



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
     */    public function getName() {
    return $this->attributes['name'];
}
public function checkIfCompulsory() {
    return $this->hasOne(Training::class,'id','training_id');
}


}
