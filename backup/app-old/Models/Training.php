<?php

namespace App\Models;

use App\Models\Interfaces\TrainingInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model implements TrainingInterface
{

    public $table = 'trainings';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'order_category_id',
        'vendors_id',
        'name',
        'description',
        'type',
        'url',
        'created_at',
        'updated_at',
        'deleted_at',
        'extension',


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

    public function orderCategory() {

        return $this->belongsTo(OrderCategory::class,'order_category_id','id');
    }



    public function vendors() {
        return $this->belongsTo(Vendor::class,'vendors_id');
    }


    public function getUrlAutoAttribute() : string
    {
        $file = $this->attributes['url'];
        return is_file(backendTrainingFile().$file) ? $file : constants('front.default.admin');
    }

    public function trainingSeen() {
        return $this->hasMany(JoeyTrainingSeen::class,'training_id','id');
    }


}
