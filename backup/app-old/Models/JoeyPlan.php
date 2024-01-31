<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyPlan extends Model
{
    const JoeysPlanTypes = [
        'per_drop_plan' => ['default|default_custom_routing|default_big_box','sub_contractor|sub_contractor_custom_routing|sub_contractor_big_box','brooker|brooker_custom_routing|brooker_big_box'],
        'by_duration'=> ['sub_hourly|downtown_hourly|sub_hourly_custom_routing|downtown_hourly_custom_routing|sub_hourly_big_box|downtown_hourly_big_box','low|high'],
        'by_area_per_drop'=> ['sub_per_drop|downtown_per_drop|sub_per_drop_custom_routing|downtown_per_drop_custom_routing|sub_per_drop_big_box|downtown_per_drop_big_box'],
        'bracket_plan'=> ['bracket_pricing|per_drop|custom_route|big_box','bracket_pricing|hourly|custom_route|big_box'],
        'dynamic_section'=>['group_zone_pricing_per_drop|custom_route|big_box'],

    ];

    public $table = 'joey_plans';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    /**
     * Get plans details
     */
    public function PlanDetails()
    {
        return $this->hasMany(JoeyPlanDetails::class,'joey_plan_id','id');
    }


    //working for mark complete route payout calculation
    public function getPlanDetailNamesAttribute()
    {
        $data = $this->plan_type;

        $data = preg_replace('/[^a-z0-9A-Z&]+/',' ',str_replace('|',' & ',$data));
        return trim(ucwords($data));
    }

}
