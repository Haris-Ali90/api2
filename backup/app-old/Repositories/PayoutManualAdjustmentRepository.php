<?php

namespace App\Repositories;

use App\Models\Interfaces\PayoutManualAdjustmentInterface;
use App\Repositories\Interfaces\PayoutManualAdjustmentRepositoryInterface;

/**
 * Class Repository
 *
 * @author Muhammad Adnan <adnanandeem1994@gmail.com>
 * @date   29/12/18
 */
class PayoutManualAdjustmentRepository implements PayoutManualAdjustmentRepositoryInterface
{
    private $model;

    public function __construct(PayoutManualAdjustmentInterface $model)
    {
        $this->model = $model;
    }

    public function modelInstance()
    {
        return $this->model;
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

    public function update($id, array $data)
    {
        $this->model::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $this->model::where('id', $id)->delete();
    }

}
