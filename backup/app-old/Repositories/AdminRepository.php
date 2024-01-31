<?php

namespace App\Repositories;

use App\Models\Interfaces\AdminInterface;
use App\Models\Interfaces\UserInterface;
use App\Repositories\Interfaces\AdminRepositoryInterface;

/**
 * Class AdminRepository
 *
 * 
 */
class AdminRepository implements AdminRepositoryInterface
{
    private $model;

    public function __construct(UserInterface $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model::all();
    }

    public function create(array $data)
    {
        $data['parent_id'] = 0;
        // $data['password'] = bcrypt($data['password']);

        $model = $this->model::create($data);

        return $model;
    }

    public function find($id)
    {
        return $this->model::where('id', $id)->first();
    }

    public function update($id, array $data)
    {
        // base admin's status cannot be set
        if ($id == 1 and array_key_exists('is_active', $data)) {
            unset($data['is_active']);
        }

        $this->model::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        // base admin cannot be deleted
        if ($id != 1) {
            $this->model::where('id', $id)->delete();
        }
    }
}
