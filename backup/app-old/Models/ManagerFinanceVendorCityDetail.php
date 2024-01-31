<?php

namespace App\Models;


use App\Models\Interfaces\ManagerFinanceVendorCityDetailInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerFinanceVendorCityDetail extends Model implements ManagerFinanceVendorCityDetailInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'finance_vendor_city_relations_detail';

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
