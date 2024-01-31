<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;

class WarehouseJoeysCount extends Model
{
    protected $table = 'warehouse_sorters';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'hub_id','date','setup_start_time','setup_end_time','start_sorting_time','end_sorting_time','internal_sorter_count','brooker_sorter_count',
        'dispensing_start_time','dispensing_end_time','manager_on_duty','dispensed_route'
    ];


  public function HubName()
    {

        return $this->belongsTo('App\FinanceVendorCity','hub_id');
    }

    public function Manager()
    {
        return $this->belongsTo(Manager::class,'manager_on_duty','id');
    }

    public function sendEmail($subject,$email, $full_name,$message)
    {
        $style = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';color: black !important;";
        $bg_img = 'background-image:url(' . url("/images/joeyco_icon_water.png") . ');';
        $bg_img = trim($bg_img);
        $body = '<div class="row" style=" width: 32%;margin: 0 AUTO;">
                <div style="text-align: center;
    background-color: lightgrey;"><img src="' . url('/') . '/images/abc.png" alt="Web Builder" class="img-responsive" style="margin:0 auto; width:150px;" /></div>
                <div style="' . $bg_img . '
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;">
                  <h1 style="' . $style . '">Hi, ' . $full_name . '!</h1>

                 <p style="' . $style . '">' .$message. ' </p>
                  <br/>


                </div>
                <div style="background-color: lightgrey;padding: 5px;">
        <p style="padding-bottom: -1px;margin: 0px;margin-left: 20px;' . $style . '">JoeyCo Inc.</p>
        <p style="margin-top: 0x;margin: 0px;margin-left: 20px;' . $style . '">16 Four Seasons Pl., Etobicoke, ON M9B 6E5</p>
        <p style="margin: 0px;margin-left: 20px;' . $style . '">+1 (855) 556-3926 · support@joeyco.com </p>
    </div>
                </div>
                ';
        // $subject = "Sorting Time Alert";
        Mail::send(array(), array(), function ($m) use ($email, $subject, $body) {
            $m->to($email)
                ->subject($subject)
                ->from(env('MAIL_USERNAME'))
                ->cc('abid.khan@joeyco.com ')
                ->setBody($body, 'text/html');
        });
    }
}
