<?php

namespace App\Models;
use App\Http\Traits\BasicModelFunctions;
use App\Models\Interfaces\JoeyJobFilterInterface;
use Illuminate\Database\Eloquent\Model;

class JoeyJobFilter extends Model implements JoeyJobFilterInterface
{
    use BasicModelFunctions;
    public $table = 'joey_job_filters';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    public function joey()
    {
        return $this->belongsTo(Joey::class);
    }

    public function zone()
    {
        return $this->belongsTo(ZoneRouting::class);
    }
}


