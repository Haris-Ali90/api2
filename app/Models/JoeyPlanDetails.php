<?php

namespace App\Models;

use App\Models\Interfaces\JoeyPlanDetailsInterface;
use Illuminate\Database\Eloquent\Model;

class JoeyPlanDetails extends Model implements JoeyPlanDetailsInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'joey_plan_details';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];


    /**
     * Get Joey plan detail group
     */

    public function JoeyPlanGroupZones()
    {
        return $this->belongsTo(JoeyPlanGroupZones::class,'group_zone_id','id');
    }

    /**
     * Get Vehicle Using Type.
     *
     * @return string
     */
    public function getPlaneTypeNameAttribute()
    {
        // formatting plan name
        $pattern = '/([|,_,]|custom_route\|big_box)/i';
        $plan_sub_name = preg_replace($pattern, ' ', $this->attributes['plane_type']);
        return  ucfirst($plan_sub_name);
    }


    /**
     * Scope a query to only include bracket plane normal.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotBracketCustomPrice($query)
    {
        return $query->where('plane_type',JoeyPlan::JoeysPlanTypes['bracket_plan'][0]);
    }
    
}
