<?php

namespace App\Models;

use App\Models\Interfaces\PayoutManualAdjustmentInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutManualAdjustment extends Model implements PayoutManualAdjustmentInterface
{

    use SoftDeletes;

    private $type_applied_labels =[
     "add",
     "subtract"
    ];

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'payout_manual_adjustment';

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
    protected $casts = [
    ];

    /**
     * ORM Relation
     *
     * @var array
     */

    /**
     * getter setter
     */
    public function getTypeAppliedLabelAttribute()
    {
        return $this->type_applied_labels[$this->type_applied];
    }

    /**
    * model helper function
    */

    public function CreatedBy()
    {
        return $this->belongsTo( User::class,'created_by', 'id')->withTrashed();
    }

}
