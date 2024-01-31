<?php

use App\Classes\FireStoreHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
 * @Debug Urls
 * */

/*Route::get('php-info', static function(){
    phpinfo();
});*/

Route::get('reset-cache', static function(){
    Artisan::call('cache:clear');
    dd('Reset Cache');
});

Route::get('config-clear', static function(){
    Artisan::call('config:clear');
    dd('Config Clear');
});

Route::get('cron', static function(){
    Artisan::call('schedule:run');
    dd('Cron');
});

Route::get('mail', static function(Request $request){
    //return backend_view('mails.test');
//    $profession = \App\Models\Profession::find(11);


    //,'rev' => 02.14, 'liquid_kf_us' => { }];

});
Route::get('test', static function(Request $request){
    $record = FireStoreHandler::deleteDocument('conversation/93/messages');
    dd($record);
});

