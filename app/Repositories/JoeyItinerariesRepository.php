<?php

namespace App\Repositories;


use App\Models\ContactUs;
use App\Models\Feedback;
use App\Models\Interfaces\JoeyItinerariesInterface;
use App\Models\Interfaces\UserInterface;
use App\Models\User;
use App\Models\Verification;
use App\Models\JoeyItineraries;
use App\Repositories\Interfaces\JoeyItinerariesRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\JoeyItinerariesLocations;

/**
 * Class UserRepository
 *
 * 
 */
class JoeyItinerariesRepository implements JoeyItinerariesRepositoryInterface
{
    private $model;

    public function __construct(JoeyItinerariesInterface $model)
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

            return JoeyItinerariesLocations::whereHas('joeyRouteLocation', function($q){
                $q->whereNull('deleted_at')
                ->whereHas('sprintTask', function ($query) {
                    $query->whereNull('deleted_at');
                })->whereHas('sprintTask.sprintsSprints', function ( $query) {
                    $query->whereNull('deleted_at')->whereNull('is_reattempt');
     
                })
                ->whereHas('sprintTask.merchantIds' ,function ( $query) {
                    $query->whereNull('deleted_at');
                });
                })->whereIn('joey_itineraries_id',$route_ids)->whereNull('deleted_at')->get();
       /*    ->whereHas('joeyRoute' ,function (Builder $query) use ($id) {
               $query->whereNull('deleted_at')->where('joey_id','=',$id);
           })*/
        //    ->whereHas('sprintTask', function (Builder $query) {
        //        $query->whereNull('deleted_at');
        //    })
        //    ->whereHas('sprintTask.sprintsSprints', function (Builder $query) {
        //        $query->whereNull('deleted_at')->whereNull('is_reattempt');

        //    })
        //    ->whereHas('sprintTask.merchantIds' ,function (Builder $query) {
        //        $query->whereNull('deleted_at');
        //    })
        //    ->whereNull('deleted_at')->whereNull('is_unattempted')->get();

    }



}
