<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class VehicleController extends ApiBaseController
{



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get Vehicle list
     *
     */
    public function list(Request $request)
    {

        DB::beginTransaction();
        try {

            $vehicle=Vehicle::get();
            $response['vehicle'] = VehicleResource::collection($vehicle);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Get Vehicle List Successfully");
    }


}
