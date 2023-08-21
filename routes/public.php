<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

Route::get('/', function () {
    return response()->json(['success' => false, 'code' => '404']);
});

Route::group(['middleware' => 'public.api'], function () {

    Route::get('/get-hierarchy', 'PublicApi\PublicApiController@get_hierarchy');
    Route::post('/create-user', 'PublicApi\PublicApiController@create_user');
    Route::patch('/update-user', 'PublicApi\PublicApiController@update_user');
    Route::delete('/delete-user', 'PublicApi\PublicApiController@delete_user');
});
