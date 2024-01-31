<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;


use App\Http\Resources\ZoneLIstResource;
use App\Http\Resources\ZonesResource;
use App\Models\Zones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ZoneController extends ApiBaseController
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
     * Get Zone list
     *
     */
    public function zoneList(Request $request)
    {

        DB::beginTransaction();
        try {
                $zones=Zones::where('deleted_at', null)->where('name','NOT LIKE','%test%')->orderBy('name')->get();

            $response = ZoneLIstResource::collection($zones);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Zone List ");
    }


}
