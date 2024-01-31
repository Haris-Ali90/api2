<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MiJobDetail;

class MiJob extends Model
{

    use SoftDeletes;

    protected $table = 'mi_jobs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','title', 'start_address', 'execution_time','type', 'start_latitude', 'start_longitude'
    ];

    public function jobDetails()
    {
        return $this->hasMany(MiJobDetail::class);
    }




}
