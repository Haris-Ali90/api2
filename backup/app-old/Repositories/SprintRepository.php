<?php

namespace App\Repositories;
use App\Models\Interfaces\SprintInterface;

use App\Models\JoeyRoutes;
use App\Repositories\Interfaces\SprintRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserRepository
 *
 *
 */
class SprintRepository implements SprintRepositoryInterface
{
    private $model;

    public function __construct(SprintInterface $model)
    {
        $this->model = $model;
    }

    public function all()
    {

        return $this->model::all();
    }

    public function create(array $data)
    {

        $model = $this->model::create($data);

        return $model;
    }

    public function find($id)
    {

        return $this->model::where('id', $id)->first();
    }

    public function findBy($attribute, $value) {
        return $this->model->where($attribute, '=', $value)->first();
    }

    public function update($id, array $data)
    {
        $this->model::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $this->model::where('id', $id)->delete();
    }


    public function findWithtask($id)
    {
        $sprintIds =  $this->model::where('active','=',1)->where('joey_id','=',$id)
         ->whereIn('status_id',[32,67,15,18,28,68,255,101,102,103,104,105,106,107,108,109,110,111,112,131,135,136,140,143,137])
            ->pluck('id');

        return $this->model::whereIn('id',$sprintIds)
            ->whereNull('deleted_at')->get();
    }

    // new work

    public function findWithtaskid($id)
    {
        $sprintdetails =  $this->model::where('id','=',$id)->first();

        return $sprintdetails;
    }


    public function findWithOrderId($ids)
    {
        return $this->model::whereIn('id',$ids)
            ->where('status_id',24)
            ->whereNull('deleted_at')->get();
    }

    // new work
    public function findJoeyLocationWithTask($id)
    {
        $notinstatus=[36,37,38,17,113,114,116,117,118,132,138,139,144];
        return $this->model::where('joey_id','=',$id)->whereNull('deleted_at')->whereNotIn('status_id', $notinstatus)->pluck('id');
    }

    public function findSprintWithJoey($id)
    {
        $notinstatus=[36,37,38,17,113,114,116,117,118,132,138,139,144];

        $sprint = JoeyRoutes::join('joey_route_locations', 'joey_route_locations.route_id', '=', 'joey_routes.id')
            ->join('sprint__tasks', 'sprint__tasks.id', '=', 'joey_route_locations.task_id')
            ->join('sprint__sprints', 'sprint__sprints.id', '=', 'sprint__tasks.sprint_id')
            ->whereNull('joey_route_locations.deleted_at')
            ->whereNull('joey_routes.deleted_at')
            ->where('joey_routes.joey_id', $id)
            ->whereNotIn('sprint__sprints.status_id', $notinstatus)
            ->pluck('sprint__sprints.id');

        return $sprint;
    }

    public function joeyOrderList($id,$startDate,$endDate)
    {

        return $this->model::where('joey_id','=',$id)
            ->whereBetween('created_at',array($startDate,$endDate))
            ->whereIn('status_id',[145, 17, 113, 114, 116, 117, 118, 132, 138, 139, 144, 105, 104, 111, 119, 106, 108, 109, 107, 131, 135, 140, 110])
            ->orderBy('created_at', 'desc')
            ->whereNull('deleted_at')->get();
    }

    public function joeyOrderDetail($id,$orderNum)
    {

        return $this->model::where('id',$orderNum)->whereNull('deleted_at')->first();
    }

}
