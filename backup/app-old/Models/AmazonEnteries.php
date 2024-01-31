<?php

namespace App\Models;
use App\Models\Interfaces\AmazonEnteriesInterface;
use Illuminate\Database\Eloquent\Model;

class AmazonEnteries extends Model implements AmazonEnteriesInterface
{

    public $table = 'amazon_enteries';


    protected $fillable = [
        'id', 'sprint_id', 'task_id', 'creator_id', 'route_id', 'ordinal', 'tracking_id', 'joey_id', 'joey_name', 'picked_up_at', 'sorted_at', 'delivered_at', 'task_status_id', 'order_image', 'address_line_1', 'address_line_2', 'address_line_3','hub_return_scan','returned_at'
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];


    public function getAmazonCountsForLoop($taskIds, $type)
    {
        $totalRecord = \DB::table('amazon_enteries')->whereIn('task_id', $taskIds)
            ->get(['sorted_at','picked_up_at','hub_return_scan','delivered_at','returned_at','task_status_id']);
        $total = 0;
        $sorted = 0;
        $pickup = 0;
        $delivered_order = 0;
        $return_orders = 0;
        $hub_return_scan = 0;
        $notscan = 0;
        $reattempted =0;
        $completion_ratio = 0;
        foreach ($totalRecord as $record)
        {
            if ($record->sorted_at != null){
                $sorted = $sorted + 1 ;
            }
            if ($record->picked_up_at != null){
                $pickup = $pickup + 1 ;
            }
            if ($record->delivered_at != null){
                $delivered_order = $delivered_order + 1 ;
            }
            if ($record->returned_at != null){
                $return_orders = $return_orders + 1 ;
            }
            if ($record->returned_at != null and $record->hub_return_scan != null){
                $hub_return_scan = $hub_return_scan + 1 ;
            }
            $total = $total + 1 ;
        }
        $notscan = count(\DB::table('amazon_enteries')->whereIn('task_id', $taskIds)->where('task_status_id',61)->pluck('task_id'));
        $reattempted = count(\DB::table('amazon_enteries')->whereIn('task_id', $taskIds)->where('task_status_id', 13)->pluck('task_id'));
        $counts['total'] = $total;
        $counts['sorted'] = $sorted;
        $counts['pickup'] = $pickup;
        $counts['delivered_order'] = $delivered_order;
        $counts['return_orders'] = $return_orders;
        $counts['hub_return_scan'] = $hub_return_scan;
        $counts['notscan'] = $notscan;
        $counts['reattempted'] = $reattempted;
        if($pickup > 0){
            $completion_ratio = round(($delivered_order/$pickup)*100,2);
        }
        $counts['completion_ratio'] = $completion_ratio;
        return $counts;
    }


}
