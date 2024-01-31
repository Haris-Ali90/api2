<?php

namespace App\Models;

use App\Models\Interfaces\JoeycoUsersInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeycoUsers extends Model implements JoeycoUsersInterface
{

    public $table = 'joeyco_user';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];
}
