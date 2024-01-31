<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;
use App\Http\Controllers\Controller;
use App\Models\GenerateToken;
use Illuminate\Support\Str;

class GenerateTokenController extends Controller
{
    public function index()
    {
        $randomToken = Str::random(60);
        $date = date('Y-m-d H:i:s');
        $currentDate = strtotime($date);
        $futureDate = $currentDate+(60*5);
        $formatDate = date("Y-m-d H:i:s", $futureDate);


        GenerateToken::create([
            'token' => $randomToken,
            'created_at' => $date,
            'expired_at' => $formatDate
        ]);

        return RestAPI::response(['token' => $randomToken], true, 'Successfully Generate Token');
    }
}
