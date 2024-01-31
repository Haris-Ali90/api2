<?php

namespace App\Repositories;

use App\Models\Interfaces\QuizAnswerInterface;


use App\Repositories\Interfaces\QuizAnswerRepositoryInterface;



class QuizAnswerRepository implements QuizAnswerRepositoryInterface
{
    private $model;

    public function __construct(QuizAnswerInterface $model)
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

    public function insert($data)
    {
        return $this->model::insert($data);
    }


}
