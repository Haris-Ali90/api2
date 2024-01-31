<?php

namespace App\Repositories;

use App\Models\Interfaces\SystemParametersInterface;
use App\Repositories\Interfaces\SystemParametersRepositoryInterface;

/**
 *
 * @author Muhammad Adnan <adnanandeem1994@gmail.com>
 * @date   30/09/2020
 */
class SystemParametersRepository implements SystemParametersRepositoryInterface
{
    private $model;

    public function __construct(SystemParametersInterface $model)
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

    public function update($id, array $data)
    {

        return $this->model::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return $this->model::where('id', $id)->delete();
    }

    public function  getKeyValue($keys)
    {
        if( gettype($keys) == 'string')
        {
            return $this->model::where('key',$keys)->first();
        }
        elseif( gettype($keys) == 'array' )
        {
            return $this->model::whereIn('key',$keys)->get()->pluck([],'key'); //->pluck('value','key')->toAraay();
        }

    }
}
