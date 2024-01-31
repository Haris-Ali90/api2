<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class VendorTransaction extends Model
{

    public $table = 'vendor_transactions';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];



    public  function earning(){
        return $this->belongsTo(FinancialTransactions::class,'transaction_id','id');
    }

}




