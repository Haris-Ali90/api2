<?php
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});
Route::get('reset/success', function (Request $request) {
    return view('admin.auth.passwords.success');
});
Route::get('joeyco-logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

Route::get('apidocs', static function (Request $request) {
    return view('documentation.index');
});

Route::get('json-file', static function(Request $request){


    //Get Json File
    $file = file_get_contents(base_path('webservices.json'));

    $decodeFile =  json_decode($file, true);

    //Replace Host With Current Host
    $decodeFile['host'] = request()->getHttpHost();

    //Set Base Path
    $decodeFile['basePath'] = str_replace('json-file', 'api/v1', request()->getRequestUri());

    //Set Scheme According To Request
    $decodeFile['schemes'] = $request->isSecure() ? ['https'] : ['http'];

    return response()->json($decodeFile);

})->name('json-url');


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
