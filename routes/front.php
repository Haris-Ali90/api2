<?php

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

/*Route::get('/', function () {
    return view('welcome');
});*/

// Auth::routes();

Route::get('/', 'HomeController@index')->name('index');
Route::get('contact', 'HomeController@contact')->name('contact');
Route::post('contact', 'HomeController@processContact')->name('process-contact');
Route::get('categories/{slug}', 'HomeController@categoryDetails')->name('category.details');

Route::get('home', function () {
    return redirect('/');
});

Route::get('thank-you', function () {
    return view('thankYou');
});

/*Route::get('/clear-cache', function() {
	Artisan::call('cache:clear');
   	Artisan::call('config:clear');
	Artisan::call('config:cache');
	Artisan::call('view:clear');
	Artisan::call('route:clear');
	return 'Cache cleared!';
});*/
