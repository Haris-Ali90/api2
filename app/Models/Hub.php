<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Hub extends Model
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'hubs';
    // Protected $primaryKey = "id";
    protected $fillable = ['title','address','city__id','created_at','updated_at','deleted_at','state_id','country__id','postal__code'];

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];




}
