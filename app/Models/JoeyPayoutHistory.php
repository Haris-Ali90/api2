<?php

namespace App\Models;

use App\Models\Interfaces\JoeyPayoutHistoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyPayoutHistory extends Model implements JoeyPayoutHistoryInterface
{

    use SoftDeletes;

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'joey_payout_history';

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
    protected $casts = [

    ];



}
