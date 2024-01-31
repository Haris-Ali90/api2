<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRequest extends Model
{
    use SoftDeletes;
    public $table = 'exchange_request';

    protected $guarded = [];

    /**
     * The attributes that should be append to toArray.
     *
     * @var array
     */

}

