<?php

namespace App\Models;

use App\Models\Interfaces\RatingsInterface;

use Illuminate\Database\Eloquent\Model;


class Ratings extends Model implements RatingsInterface
{

    public $table = 'ratings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','creator_type','creator_id','object_type','object_id','context_type',
        'context_id','rating','lowest','highest','notes','created_at'
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



    ### for vendor
    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'id','creator_id');
    }
    ### for vendor
    public function joey()
    {
        return $this->hasOne(Joey::class, 'id','creator_id');
    }


}


