<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Http\Resources\ManagerListResource;
use App\Models\Dashboard;
use App\Models\Manager;

class ManagerController extends Controller
{
    public function index()
    {
        $managers = Manager::whereNull('deleted_at')->get();
        $response = ManagerListResource::collection($managers);
        return RestAPI::response($response, true, "Managers Fetch Successfully");
    }
}
