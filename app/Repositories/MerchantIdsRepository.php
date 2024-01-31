<?php

namespace App\Repositories;

use App\Models\Interfaces\JoeyRoutesInterface;
use App\Models\Interfaces\MerchantIdsInterface;
use App\Models\Interfaces\SprintInterface;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Repositories\Interfaces\MerchantsIdsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserRepository
 *
 * 
 */
class MerchantIdsRepository implements MerchantsIdsRepositoryInterface
{
    private $model;

    public function __construct(MerchantIdsInterface $model)
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


    public function findWithTrackingId($id)
    {
        dd('here');
       return $this->model::where('joey_id','=',$id)->whereNull('deleted_at')
        // ->whereHas('joeyRouteLocation' ,function (Builder $query) {
        //         $query->whereNull('deleted_at');
        //     })
        //     ->whereHas('joeyRouteLocation.sprintTask', function (Builder $query) {
        //         $query->whereNull('deleted_at');
        //     })
        //      ->whereHas('joeyRouteLocation.sprintTask.merchantIds' ,function (Builder $query) {
        //          $query->whereNull('deleted_at');
        //         })
                ->get();

    }



}
