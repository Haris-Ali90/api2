<?php

namespace App\Repositories;

use App\Models\Interfaces\JoeyRoutesInterface;
use App\Models\Interfaces\JoeyTransactionsInterface;
use App\Models\Interfaces\SprintInterface;
use App\Models\JoeyRouteLocation;
use App\Repositories\Interfaces\JoeyRouteRepositoryInterface;
use App\Repositories\Interfaces\JoeyTransactionsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserRepository
 *
 */
class JoeyTransactionsRepository implements JoeyTransactionsRepositoryInterface
{
    private $model;

    public function __construct(JoeyTransactionsInterface $model)
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


    public function joeyCalculationForSummary($id,$type,$toDate,$fromDate)
    {
        return $this->model->whereHas('earning' ,function (Builder $query) use($toDate,$fromDate){
            $query->whereBetween('created_at',[$toDate, $fromDate]);
        })->where('joey_id',$id)->where('type','=',$type)->get();
    }


    public function joeyCalculationForRating($id,$type)
    {
        return $this->model->whereHas('earning' ,function (Builder $query) {
        })->where('joey_id',$id)->where('type','=',$type)->get();
    }

//    public function joeyTip($id,$type,$toDate,$fromDate)
//    {
//        return $this->model->whereHas('earning' ,function (Builder $query) use($toDate,$fromDate){
//            $query->whereBetween('creatsed_at',[$toDate, $fromDate]);
//        })->where('joey_id',$id)->where('type','=',$type)->get();
//    }
    // new work
  public function joeyCalculationForSummaryMultiple($id,$type=[],$toDate,$fromDate)
    {
        return $this->model->whereHas('earning' ,function (Builder $query) use($toDate,$fromDate){
            $query->whereBetween('created_at',[$toDate, $fromDate]);
        })->where('joey_id',$id)->whereIn('type',$type)->get();
    }
 public function joeyCalculationForRatingMultiple($id,$type=[])
    {
        return $this->model->whereHas('earning' ,function (Builder $query) {
        })->where('joey_id',$id)->whereIn('type',$type)->get();
    }

    public function getDurationOfJoey($id,$startDate,$endDate)
    {
        return $this->model->where('type', 'sprint')->whereHas('earning' ,function (Builder $query) use($startDate,$endDate){
            $query->whereBetween('created_at',[$startDate, $endDate]);
        })->where('joey_id',$id)->get();

//        return $this->model->with('earning')->whereNull('deleted_at')->where('joey_id',$id)->get();
    }

}
