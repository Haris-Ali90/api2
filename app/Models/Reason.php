<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{

    protected $table = 'reasons';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'title', 'created_at','updated_at','deleted_at',
    ];




}
