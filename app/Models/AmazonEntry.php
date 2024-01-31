<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonEntry extends Model
{

    protected $table = 'amazon_enteries';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       "sprint_id" ,"task_id" ,"creator_id" ,"route_id" ,"ordinal" ,"tracking_id","joey_id" ,"joey_name","picked_up_at","sorted_at","delivered_at","task_status_id","order_image","address_line_1","address_line_2","address_line_3","created_at","updated_at","deleted_at","is_custom_route",
    ];




}
