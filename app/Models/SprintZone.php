<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SprintZone extends Model
{
    use SoftDeletes;

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'sprint__sprint_zone';
    // Protected $primaryKey = "id";


    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];




}
