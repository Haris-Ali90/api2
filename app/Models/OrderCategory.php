<?php

namespace App\Models;


use App\Models\Interfaces\OrderCategoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCategory extends Model implements OrderCategoryInterface
{
    use SoftDeletes;
    public $table = 'order_category';

    protected $guarded = [];

    protected $fillable = [
        'id','name','score','order_count','type','quiz_limit','score'

    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['created_at','updated_at'];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */
    protected $appends = [];

    public  function mulitpleTrainingAgainstCategoryId(){
        return $this->hasMany(Training::class,'order_category_id','id')->whereNull('trainings.deleted_at');
    }
    public  function singleTrainingAgainstCategoryId(){
        return $this->hasOne(Training::class,'order_category_id','id')->whereNull('trainings.deleted_at');
    }
    public  function quizQuestion(){
        return $this->hasMany(QuizQuestion::class,'order_category_id','id');
    }
    public function getcumpulsorytrainings() {
        return $this->hasMany(Training::class,'order_category_id','id')->where('is_compulsory',1)->whereNull('trainings.deleted_at');
    }
}
