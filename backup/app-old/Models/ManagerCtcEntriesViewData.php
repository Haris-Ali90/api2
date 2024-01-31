<?php

namespace App\Models;

use App\Http\Traits\BasicModelFunctions;
use App\Models\Interfaces\ManagerCtcEntriesViewDataInterface;
use Illuminate\Database\Eloquent\Model;


class ManagerCtcEntriesViewData extends Model implements ManagerCtcEntriesViewDataInterface
{
    use BasicModelFunctions;
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'view_ctc_data';

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

    public function getManagerCtcCounts($taskIds, $type)
    {
        if (in_array($type, ['all','total'])) {
            $counts['total'] = $this->ctctotalOrdersWithCustom($taskIds);
        }
        if (in_array($type, ['all', 'sorted'])) {
            $counts['sorted'] = $this->ctcsortedWithCustom($taskIds);
        }
        if (in_array($type, ['all', 'picked'])) {
            $counts['pickup'] = $this->ctcpickupWithCustom($taskIds);
        }
        if (in_array($type, ['all', 'delivered'])) {
            $counts['delivered_order'] = $this->ctcdelivery_orderWithCustom($taskIds);
        }
        if (in_array($type, ['all', 'return'])) {
            $counts['return_orders'] = $this->ctcreturn_ordersWithCustom($taskIds);
            $counts['hub_return_scan'] = $this->ctchub_return_scanWithCustom($taskIds);
        }
        if (in_array($type, ['all', 'scan'])) {
            $counts['notscan'] = $this->ctcnotscanWithCustom($taskIds);
        }
        return $counts;
    }

    public function ctctotalOrdersWithCustom($taskIds)
    {
        $total = $this->whereIn('task_id', $taskIds)->pluck('task_id');
        return count($total);
    }

    public function ctcsortedWithCustom($taskIds)
    {
        $sorted = $this->whereIn('task_id', $taskIds)->whereNotNull('sorted_at')->pluck('task_id');
        return count($sorted);
    }

    public function ctcpickupWithCustom($taskIds)
    {
        $pickup = $this->whereIn('task_id', $taskIds)->whereNotNull('picked_up_at')->pluck('task_id');
        return count($pickup);
    }

    public function ctcdelivery_orderWithCustom($taskIds)
    {
        return $delivery_order = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('competed'))->count();
    }

    public function ctcreturn_ordersWithCustom($taskIds)
    {
        return $return_orders = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('return'))->count();
    }

    public function ctchub_return_scanWithCustom($taskIds)
    {
        return $hub_return_scan = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', $this->getStatusCodes('return'))->whereNotNull('hub_return_scan')->count();
    }

    public function ctcnotscanWithCustom($taskIds)
    {
        return $notscan = $this->whereIn('task_id', $taskIds)->whereIn('task_status_id', [61, 13])->count();
    }

}
