<?php

namespace App\Models;


use App\Http\Traits\BasicModelFunctions;
use App\Models\Interfaces\ManagerAmazonEntriesViewDataInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerAmazonEntriesViewData extends Model implements ManagerAmazonEntriesViewDataInterface
{
    use BasicModelFunctions;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'view_amazon_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
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

    //Getting Amazon Counts
    public function getManagerAmazonCounts($taskIds, $type)
    {
        if (in_array($type, ['all','total'])) {
            $counts['total'] = $this->managerTotalOrders($taskIds);
        }
        if (in_array($type, ['all', 'sorted'])) {
            $counts['sorted'] = $this->managerSorted($taskIds);
        }
        if (in_array($type, ['all', 'picked'])) {
            $counts['pickup'] = $this->managerPickup($taskIds);
        }
        if (in_array($type, ['all', 'delivered'])) {
            $counts['delivered_order'] = $this->managerDelivery_order($taskIds);
        }
        if (in_array($type, ['all', 'return'])) {
            $counts['return_orders'] = $this->managerReturn_orders($taskIds);
            $counts['hub_return_scan'] = $this->managerHub_return_scan($taskIds);
        }
        if (in_array($type, ['all', 'scan'])) {
            $counts['notscan'] = $this->managerNotscan($taskIds);
        }
        return $counts;
    }

    //Getting total order
    public function managerTotalOrders($taskIds)
    {
        $total = $this->whereIn('task_id', $taskIds)->where('is_custom_route', 0)->pluck('task_id');
        return count($total);
    }

    //Getting sorted order
    public function managerSorted($taskIds)
    {
        $sorted = $this->whereIn('task_id', $taskIds)->whereNotNull('sorted_at')->where('is_custom_route', 0)->pluck('task_id');
        return count($sorted);
    }

    //Getting pickup order
    public function managerPickup($taskIds)
    {
        $pickup = $this->whereIn('task_id', $taskIds)->whereNotNull('picked_up_at')->where('is_custom_route', 0)->pluck('task_id');
        return count($pickup);
    }

    //Getting delivery order
    public function managerDelivery_order($taskIds)
    {
        return $delivery_order = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('competed'))->where('is_custom_route', 0)->count();
    }

    //Getting return order
    public function managerReturn_orders($taskIds)
    {
        return $return_orders = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('return'))->where('is_custom_route', 0)->count();
    }

    //Getting hub return order
    public function managerHub_return_scan($taskIds)
    {
        return $hub_return_scan = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('return'))->whereNotNull('hub_return_scan')->where('is_custom_route', 0)->count();
    }

    //Getting not scan order
    public function managerNotscan($taskIds)
    {
        return $notscan = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', [61, 13])->where('is_custom_route', 0)->count();
    }


}
