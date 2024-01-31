<?php

namespace App\Models;
use App\Models\Interfaces\ComplaintInterface;
use App\Models\Interfaces\VehicleInterface;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model implements ComplaintInterface
{

    public $table = 'complaints';



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','joey_id','order_id','type','description',
        'status'
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
