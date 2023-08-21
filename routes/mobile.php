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

Route::post('auth/login', 'Mobile\AuthController@login');

Route::group(['middleware' => 'mobile.auth'], function () {

    Route::group(['prefix' => 'material-stocks'], function () {
        Route::get('/', 'CMS\MaterialStockController@index');
        Route::post('/reduce-fifo', 'CMS\MaterialStockController@reduce_fifo');
    });

    Route::get('trouble-tickets', 'CMS\TroubleTicketController@index');
    Route::get('project-code', 'Mobile\ProjectController@index');

    Route::get('main-warehouses', 'Mobile\MainWarehouseController@index');
    Route::get('transit-warehouses', 'Mobile\TransitWarehouseController@index');
    Route::get('lastmile-warehouses', 'Mobile\LastmileWarehouseController@index');

    Route::group(['prefix' => 'material-to-site'], function () {
        Route::get('/', 'Mobile\MaterialToSiteController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'Mobile\MaterialToSiteController@index');
        Route::post('/create', 'Mobile\MaterialToSiteController@create');
        Route::post('/update', 'Mobile\MaterialToSiteController@update');
        Route::post('/confirm', 'Mobile\MaterialToSiteController@confirm');
        Route::post('/close', 'Mobile\MaterialToSiteController@close');
        Route::post('/upload-photo', 'Mobile\MaterialToSiteController@upload_photo');
        Route::delete('/remove-photo', 'Mobile\MaterialToSiteController@remove_photo');
    });

    Route::group(['prefix' => 'transfer-material'], function () {
        Route::get('/', 'Mobile\TransferMaterialController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'Mobile\TransferMaterialController@index');
        Route::post('/create', 'Mobile\TransferMaterialController@create');
        Route::post('/update', 'Mobile\TransferMaterialController@update');
        Route::post('/receive', 'Mobile\TransferMaterialController@receive');
    });

    Route::group(['prefix' => 'used-material-returns'], function () {
        Route::get('/', 'Mobile\UsedMaterialReturnController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'Mobile\UsedMaterialReturnController@index');
        Route::post('/create', 'Mobile\UsedMaterialReturnController@create');
        Route::post('/update', 'Mobile\UsedMaterialReturnController@update');
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', 'CMS\NotificationController@index');
        Route::get('/count', 'CMS\NotificationController@count');
        Route::post('/read', 'CMS\NotificationController@read');
        Route::post('/delete', 'CMS\NotificationController@delete');
    });
});
