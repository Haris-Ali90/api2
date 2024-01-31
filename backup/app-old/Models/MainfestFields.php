<?php

namespace App\Models;

use App\Models\Interfaces\MainfestFieldsInterface;
use App\Models\Interfaces\SprintInterface;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class MainfestFields extends Model implements MainfestFieldsInterface
//extends Model implements JoeyRoutesInterface
{

    public $table = 'mainfest_fields';

    use SoftDeletes,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','vendor_id','sprint_id','xsi','sendingPartyID', 'receivingPartyID','warehouseLocationID',
        'manifestCreateDateTime','carrierInternalID','manifestNumber','carrierAccountID','shipmentDate',
        'currencyCode','shipFromAddressType','shipFromAddressName',
        'shipFromAddressLine1','shipFromAddressCity','shipFromAddressStateProvince',
        'shipFromAddressZip','shipFromAddressCountryCode','shipFromAddressCountryName',
        'amazonTaxID','customerOrderNumber','consigneeAddressType','consigneeAddressName','consigneeAddressLine2',
        'consigneeAddressLine3','consigneeAddressLine1','consigneeAddressCity',
        'consigneeAddressStateProvince','consigneeAddressZip','consigneeAddressCountryCode',
        'consigneeAddressCountryName','consigneeAddressContactPhone','consigneeAddressContactEmail','AmzShipAddressUsage','AddressType',
        'rbc_deposit_number','cash_on_hand','timezone',
        'SafePlace','DeliverToCustOnly','IsSignatureRequired',
        'AgeVerificationRequired','encryptedShipmentID','packageID','trackingID','batteryStatements',
        'amazonTechnicalName',
        'shipZone','shipSort','scheduledDeliveryDate',
        'valueOfGoodsChargeOrAllowance','valueOfGoodsMonetaryAmount','valueOfGoodsCurrencyISOCode','packageCostChargeOrAllowance','packageCostMonetaryAmount',
        'packageCostCurrencyISOCode','declaredWeightValue','declaredUnitOfMeasure',
        'actualWeightValue','actualUnitOfMeasure','lengthValue',
        'lengthUnitOfMeasure','heightValue','heightUnitOfMeasure','widthValue','widthUnitOfMeasure',
        'totalShipmentQuantity','totalShipmentQuantityUnitOfMeasure','totalShipmentValue',
        'totalShipmentValueCurrencyISOCode','totalDeclaredGrossWeight','totalDeclaredGrossWeightUnitOfMeasure',
        'totalActualGrossWeight','totalActualGrossWeightUnitOfMeasure'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    //     ### for multiple task from sprint id
    // public  function sprintTask(){
    //     return $this->hasMany(SprintTasks::class,'sprint_id','id');
    // }

    // ### for sprint task history
    // public function sprintHistory(){
    //     return $this->hasMany(SprintTaskHistory::class,'sprint_id','id');
    // }



}
