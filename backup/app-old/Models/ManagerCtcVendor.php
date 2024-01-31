<?php

namespace App\Models;


use App\Models\Interfaces\ManagerCtcVendorsInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerCtcVendor extends Model implements ManagerCtcVendorsInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'ctc_vendor';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
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
}
