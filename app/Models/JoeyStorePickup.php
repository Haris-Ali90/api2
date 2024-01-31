<?php

namespace App\Models;

use App\Models\Interfaces\JoeyStorePickupInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JoeyStorePickup extends Model implements JoeyStorePickupInterface
{

    public $table = 'joey_storepickup';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','route_id','joey_id','tracking_id','sprint_id','task_id','status_id'
    ];

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


    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }



}


