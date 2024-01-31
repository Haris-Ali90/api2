<?php

namespace App\Repositories;

use App\Models\Interfaces\CustomerFlagCategoryInterface;
use App\Repositories\Interfaces\CustomerFlagCategoryRepositoryInterface;



class CustomerFlagCategoryRepository implements CustomerFlagCategoryRepositoryInterface
{
    private $model;

    public function __construct(CustomerFlagCategoryInterface $model)
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

    public function updateOrCreate($id,array $data)
    {

       return $this->model::updateOrCreate(['id'=>$id],$data);
    }

    public function insert(array $data)
    {
        $model = $this->model::insert($data);
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

    public function deleteSubCategory($id)
    {

        $sub_cat = $this->model::where('id', $id)->first();
        $sub_cat->getParent->decrement('have_childs');
        $this->model::where('id', $id)->delete();


    }


}
