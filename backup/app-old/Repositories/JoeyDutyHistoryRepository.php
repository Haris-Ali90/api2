<?php

namespace App\Repositories;


use App\Models\ContactUs;
use App\Models\Feedback;
use App\Models\Interfaces\JoeyDutyHistoryInterface;
use App\Models\Interfaces\JoeyInterface;
use App\Models\Interfaces\UserInterface;
use App\Models\User;
use App\Models\Verification;
use App\Repositories\Interfaces\JoeyDutyHistoryRepositoryInterface;
use App\Repositories\Interfaces\JoeyRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * Class UserRepository
 *
 * 
 */
class JoeyDutyHistoryRepository implements JoeyDutyHistoryRepositoryInterface
{
    private $model;

    public function __construct(JoeyDutyHistoryInterface $model)
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
    public function isWorking($id)
    {

        return $this->model::where('joey_id', '=', $id)->whereNotNull('started_at')->whereNull('ended_at')->count() > 0;
    }


}
