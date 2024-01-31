<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
class VendorPackage extends Model
{


    public $table = 'vendor_package';
    private $vehileCharge = null;
    private $IsvehileCharge = false;
    private $vehileAllowance = null;
    private $IsvehileAllowance = false;

    use SoftDeletes,Notifiable;



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [


    ];

    // new work
    public function vehicleCharge()
    {
        $prefix = 'PL-';

        if(!$this->IsvehicleCharge)
        {
            $this->vehicleCharge = VehicleCharge::where('plan_id',$prefix.$this->package_id)->get();
            $this->IsvehicleCharge = true;
        }
        return  $this->vehicleCharge;

    }
    // new work
    public function vehicleAllowance()
    {
        $prefix = 'PL-';

        
        if(!$this->IsvehicleAllowance)
        {
            $this->vehicleAllowance = VehicleAllowance::where('plan_id',$prefix.$this->package_id)->get();
            $this->IsvehicleAllowance = true;
        }
        return  $this->vehicleAllowance;

    }



}
