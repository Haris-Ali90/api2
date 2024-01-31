<?php

namespace App\Repositories;

use App\Models\Interfaces\JoeyRoutesInterface;
use App\Models\Interfaces\SprintInterface;
use App\Models\JoeyRouteLocation;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserRepository
 *
 *
 */
class JoeyRouteRepository implements JoeyRouteRepositoryInterface
{
    private $model;

    public function __construct(JoeyRoutesInterface $model)
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


    public function findWithJoeyId($id)
    {
            $route_ids =  $this->model::where('joey_id',$id)->whereNull('deleted_at')->pluck('id');

       return JoeyRouteLocation::whereIn('route_id',$route_ids)
       /*    ->whereHas('joeyRoute' ,function (Builder $query) use ($id) {
               $query->whereNull('deleted_at')->where('joey_id','=',$id);
           })*/
           ->whereHas('sprintTask', function (Builder $query) {
               $query->whereNull('deleted_at');
           })
           ->whereHas('sprintTask.sprintsSprintsForRoute', function (Builder $query) {
               $query->whereNull('deleted_at')
			   //->whereNull('is_reattempt')
			   ->where('is_reattempt','!=',1);

           })
           ->whereHas('sprintTask.merchantIds' ,function (Builder $query) {
               $query->whereNull('deleted_at');
           })
           ->whereNull('deleted_at')->whereNull('is_unattempted')->get();

       /* ->whereHas('joeyRouteLocation' ,function (Builder $query) {
                $query->whereNull('deleted_at')->whereNull('is_unattempted');
            })
            ->whereHas('joeyRouteLocation.sprintTask', function (Builder $query) {
                $query->whereNull('deleted_at');
            })
           ->whereHas('joeyRouteLocation.sprintTask.sprintsSprints', function (Builder $query) {
               $query->whereNull('deleted_at')->whereNull('is_reattempt');

           })
             ->whereHas('joeyRouteLocation.sprintTask.merchantIds' ,function (Builder $query) {
                 $query->whereNull('deleted_at');
                })*/


    }

    public function optimizeOnlyLastMileByJoeyId($id)
    {
        $route_ids =  $this->model::where('joey_id',$id)->where('mile_type',3)->whereNull('deleted_at')->pluck('id');

        return JoeyRouteLocation::whereIn('route_id',$route_ids)
            /*    ->whereHas('joeyRoute' ,function (Builder $query) use ($id) {
                    $query->whereNull('deleted_at')->where('joey_id','=',$id);
                })*/
            ->whereHas('sprintTask', function (Builder $query) {
                $query->whereNull('deleted_at');
            })
            ->whereHas('sprintTask.sprintsSprintsForRoute', function (Builder $query) {
                $query->whereNull('deleted_at')
                    //->whereNull('is_reattempt')
                    ->where('is_reattempt','!=',1);

            })
            ->whereHas('sprintTask.merchantIds' ,function (Builder $query) {
                $query->whereNull('deleted_at');
            })
            ->whereNull('deleted_at')->whereNull('is_unattempted')->get();

        /* ->whereHas('joeyRouteLocation' ,function (Builder $query) {
                 $query->whereNull('deleted_at')->whereNull('is_unattempted');
             })
             ->whereHas('joeyRouteLocation.sprintTask', function (Builder $query) {
                 $query->whereNull('deleted_at');
             })
            ->whereHas('joeyRouteLocation.sprintTask.sprintsSprints', function (Builder $query) {
                $query->whereNull('deleted_at')->whereNull('is_reattempt');

            })
              ->whereHas('joeyRouteLocation.sprintTask.merchantIds' ,function (Builder $query) {
                  $query->whereNull('deleted_at');
                 })*/


    }



}
