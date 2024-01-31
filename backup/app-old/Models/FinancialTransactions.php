<?php

namespace App\Models;

use App\Models\Interfaces\FinancialTransactionsInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransactions extends Model implements FinancialTransactionsInterface
{

    public $table = 'financial_transactions';
    use SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','reference','description','amount','merchant_order_num'
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

    public static function getAssociatedShift($sprintId, $joeyId)
    {
        $financialTransaction = FinancialTransactions::where('reference', 'CR-'.$sprintId)->first();
        if(isset($financialTransaction)){
            $associated = JoeyTransactions::where('joey_id', $joeyId)->where('transaction_id', $financialTransaction->id)->first();
            if(isset($associated)){
                return $associated->shift_id;
            }
        }
        return '';
    }


    }


