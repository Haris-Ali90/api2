<?php

namespace App\Repositories;


use App\Models\ContactUs;
use App\Models\Feedback;
use App\Models\Interfaces\UserInterface;

use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * Class UserRepository
 *
 *
 */
class UserRepository implements UserRepositoryInterface
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


    public function findWithVehicle($id)
    {
        return $this->model::with('vehicle')->where('id', $id)->first();
    }


    public function isBusy($id){

            return $this->model::with('Busy')->where('id',$id)->first();
    }
    public function findOnDuty($attribute, $value) {
        return $this->model->where($attribute, '=', $value)->first();
    }


    public  function fix($pos) {


        if (is_array($pos)) {
            foreach ($pos as &$p)
                $p = self::fix($p);
        } else if ($pos < -100000 || $pos > 100000) {
            return sprintf('%.6f', $pos / 1000000);
        }

        return $pos;
    }

    public  function unfix($pos) {

        $pos = $this->fix($pos);

        if (is_array($pos)) {
            foreach ($pos as &$p)
                $p = self::unfix($p);
        } else {
            return intval($pos * 1000000);
        }

        return $pos;
    }


//    public function findJoeyDeposit($id)
//    {
//        return $this->model::with('deposit')->where('id', $id)->first();
//    }




}
