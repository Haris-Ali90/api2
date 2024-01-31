<?php

namespace App\Models;

use App\Models\Interfaces\SprintSprintHistoryInterface;

use Illuminate\Database\Eloquent\Model;


class SprintSprintHistory extends Model implements SprintSprintHistoryInterface
{
    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'sprint__sprints_history';

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "sprint__sprints_id",
        "joey_id",
        "vehicle_id",
        "distance",
        "status_id",
        "active",
        "optimize_route",
        "date"
    ];

}
