<?php

namespace App\Models;

use App\Models\Interfaces\MerchantOrderCsvUploadDetailInterface;
use Illuminate\Database\Eloquent\Model;


class MerchantOrderCsvUploadDetail extends Model implements MerchantOrderCsvUploadDetailInterface
{

    public $table = 'merchant_order_csv_upload_details';

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

}
