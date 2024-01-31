<?php

namespace App\Repositories;

use App\Models\Interfaces\CategoryInterface;
use App\Models\Interfaces\NotificationInterface;
use App\Models\Interfaces\ProfessionInterface;
use App\Models\Notification;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\ProfessionRepositoryInterface;
use DB;

/**
 * Class CategoryRepository
 *
 * @author Ghulam Mustafa <ghulam.mustafa@vservices.com>
 * @date   05/10/18
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    private $model;

    public function __construct(NotificationInterface $model)
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
        $this->model::where('user_id', $id)->update($data);
    }

    public function delete($id)
    {
        $this->model::where('id', $id)->delete();
    }

    public function getnotification($id,$limit)
    {
        $this->update($id, ['is_read' => 1]);
        return $this->model::select('id','created_at','notification_data','notification_type','notification')
          ->where('user_id',$id)
            ->orderBy('created_at', 'DESC')->paginate($limit??20);
    }



}
