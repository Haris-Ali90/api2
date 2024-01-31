<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\FinanceVendorCity;
use App\Models\ManagerDashboard;

class CityController extends Controller
{
    public function index()
    {
        $user = ManagerDashboard::where('id', jwt_manger()->user()->id)->first();
        $hubPermissions = explode(',', $user->statistics);
        $cities = FinanceVendorCity::whereNull('deleted_at')->whereIn('id', $hubPermissions)->get();
        $response = CityResource::collection($cities);
        return RestAPI::response($response, true, "Cities Fetch Successfully");
    }
}
