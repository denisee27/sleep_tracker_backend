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
    abort(404);
});

Route::post('auth/login', 'CMS\AuthController@login');
Route::post('auth/register', 'CMS\AuthController@register');

Route::group(['middleware' => 'auth'], function () {

    Route::get('/print-label', 'CMS\PrintLabelController@index');

    Route::group(['prefix' => 'auth'], function () {
        Route::get('/logout', 'CMS\AuthController@logout');
        Route::get('/profile', 'CMS\AuthController@profile');
        Route::post('/refresh', 'CMS\AuthController@refresh');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'CMS\UserController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\UserController@index');
        Route::post('/update', 'CMS\UserController@update');
    });
    Route::group(['prefix' => 'jobs'], function () {
        Route::get('/', 'CMS\JobMasterController@index');
        Route::get('/{ibd:[a-zA-Z-?0-9]+}', 'CMS\JobMasterController@index');
        Route::post('/create', 'CMS\JobMasterController@create');
        Route::post('/update', 'CMS\JobMasterController@update');
        Route::delete('/delete', 'CMS\JobMasterController@delete');
    });
});
