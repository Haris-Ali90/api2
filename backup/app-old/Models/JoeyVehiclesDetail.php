<?php

namespace App\Models;
use App\Models\Interfaces\JoeyVehiclesDetailInterface;
use Illuminate\Database\Eloquent\Model;

class JoeyVehiclesDetail extends Model implements JoeyVehiclesDetailInterface
{

    public $table = 'joey_vehicles_detail';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','vehicle_id','joey_id','license_plate','color',
        'model','make'
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

    ### for Vehicle
    public  function vehicle(){
        return $this->belongsTo(Vehicle::class,'vehicle_id','id');
    }



















}
