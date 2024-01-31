<?php

namespace App\Models;
use App\Models\Interfaces\FailedXmlOrdersInterface;
use Illuminate\Database\Eloquent\Model;

class XmlFailedOrders extends Model implements FailedXmlOrdersInterface
{

    public $table = 'xml_failed_orders';

    /**
     * The attributes that are mass assignable.
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
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

}
