<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MiJobRoute extends Model
{

    use SoftDeletes;

    protected $table = 'mi_job_routes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function miJob()
    {
        return $this->belongsTo(MiJob::class);
    }

    public function route()
    {
        return $this->belongsTo(JoeyRoute::class);
    }



}
