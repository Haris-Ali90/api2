<?php

namespace App\Models;

use App\Models\Interfaces\BorderlessInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class BorderlessDashboard extends Model implements BorderlessInterface
{

    use SoftDeletes;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'boradless_dashboard';

    // Protected $primaryKey = "id";
    protected $fillable = [  'id' , 'sprint_id' , 'task_id' , 'creator_id' , 'route_id' , 'ordinal' , 'tracking_id' , 'joey_id' ,'eta_time','store_name','customer_name','weight' ,'joey_name' , 'picked_up_at' , 'sorted_at' , 'delivered_at' , 'returned_at' , 'hub_return_scan' , 'task_status_id' , 'order_image' , 'address_line_1' , 'address_line_2' , 'address_line_3' , 'created_at' , 'updated_at' , 'deleted_at' , 'is_custom_route'
    ];

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
    protected $casts = [];

    // hubs  function relation
    public function hub()
    {
        return $this->belongsTo(Hub::class,'hub_id','id');
    }



}
