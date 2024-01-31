<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MiJob;

class MiJobDetail extends Model
{

    use SoftDeletes;
    protected $table = 'mi_job_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mi_job_id', 'locationid', 'location_type','type','start_time', 'end_time'
    ];

    public function miJob()
    {
        return $this->belongsTo(MiJob::class);
    }




}
