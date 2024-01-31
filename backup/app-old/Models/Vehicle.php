<?php

namespace App\Models;
use App\Models\Interfaces\VehicleInterface;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model implements VehicleInterface
{

    public $table = 'vehicles';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','name','max_distance','pickup_price','distance_price',
        'dropoff_price','distance_allowance',
        'third_party_pickup_price','ordinal','capacity','min_visits',
        'make','color','model','license_plate'
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
