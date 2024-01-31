<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
class VehicleAllowance extends Model
{


    public $table = 'vehicle_allowance';

    use Notifiable;



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [


    ];





}
