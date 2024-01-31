<?php

namespace App\Models;
use App\Models\Interfaces\VendorInterface;
use App\Models\Interfaces\WorkTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkTime extends Model implements WorkTimeInterface
{

    public $table = 'prefer_work_times';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'start_time',
        'end_time',
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
