<?php

namespace App\Repositories;
use App\Models\Interfaces\SprintInterface;
use App\Models\Interfaces\SprintTaskInterface;
use App\Repositories\Interfaces\SprintRepositoryInterface;
use App\Repositories\Interfaces\SprintTaskRepositoryInterface;

/**
 * Class UserRepository
 *
 * 
 */
class SprintTaskRepository implements SprintTaskRepositoryInterface
{
    private $model;

    public function __construct(SprintTaskInterface $model)
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


    public function findWithSprint($id)
    {
           
        return $this->model::where('sprint_id','=',$id)
        ->whereNull('deleted_at')
        ->first();
    }


}
