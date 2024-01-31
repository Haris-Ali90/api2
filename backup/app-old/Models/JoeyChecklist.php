<?php

namespace App\Models;
use App\Models\Interfaces\JoeyChecklistInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyChecklist extends Model implements JoeyChecklistInterface
{

    public $table = 'joeychecklist';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title',
        'category_id',
        'deleted_at',
        'created_at',
        'updated_at',

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
    public function category() {
        return $this->belongsTo(OrderCategory::class,'category_id','id');
    }
}
