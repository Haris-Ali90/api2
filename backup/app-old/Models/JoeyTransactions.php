<?php

namespace App\Models;

use App\Models\Interfaces\JoeyTransactionsInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JoeyTransactions extends Model implements JoeyTransactionsInterface
{

    public $table = 'joey_transactions';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id','joey_id','type','payment_method','distance',
        'duration','date_identifier','shift_id','balance'
    ];

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




