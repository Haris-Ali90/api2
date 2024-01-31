<?php

namespace App\Models;
use App\Models\Interfaces\VendorInterface;
use App\Models\Interfaces\WorkTimeInterface;
use App\Models\Interfaces\WorkTypeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkType extends Model implements WorkTypeInterface
{

    public $table = 'prefer_work_type';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'joey_id',



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


}
