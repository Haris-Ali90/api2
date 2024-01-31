<?php

namespace App\Models;

use App\Models\Interfaces\OrderImageInterface;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class OrderImage extends Model implements OrderImageInterface

{

    public $table = 'order_images';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','tracking_id','task_id','image'
    ];




}
