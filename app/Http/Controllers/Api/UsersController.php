<?php

namespace App\Http\Controllers\Api;

use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UsersController extends Controller{

    public function export(){
        
        Excel::store(new UsersExport, 'excel/data1.xlsx', 'public');

        // returning pah to open file on click
        $path =  $_SERVER['SERVER_NAME'].'/api2/storage/app/public/excel/data1.pdf';   
        
        return $path;

//        return Excel::download(new UsersExport, 'users.xlsx');

    }
}
