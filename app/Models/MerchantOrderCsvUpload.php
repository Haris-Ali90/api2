<?php

namespace App\Models;

use App\Models\Interfaces\MerchantOrderCsvUploadInterface;
use Illuminate\Database\Eloquent\Model;


class MerchantOrderCsvUpload extends Model implements MerchantOrderCsvUploadInterface
{

    public $table = 'merchant_order_csv_upload';

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


    // ORM Relations

    /**
     * Get the merchant order csv upload details.
     */
    public function MerchantOrderCsvUploadDetails()
    {
        return $this->hasMany(MerchantOrderCsvUploadDetail::class,'merchant_order_csv_upload_id','id');
    }
    public function MerchantOrderCsvUploadDetailsWithAprrovedStatus()
    {
        return $this->hasMany(MerchantOrderCsvUploadDetail::class,'merchant_order_csv_upload_id','id')->where('status',1)->orderBy('id');
    }
    public function vendorDetails()
    {
        return $this->hasOne(Vendor::class,'id','vendor_id');
    }


}
