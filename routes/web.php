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

Route::group(['middleware' => 'auth'], function () {

    Route::get('/print-label', 'CMS\PrintLabelController@index');

    Route::group(['prefix' => 'auth'], function () {
        Route::get('/logout', 'CMS\AuthController@logout');
        Route::get('/nav', 'CMS\AuthController@navigation');
        Route::get('/profile', 'CMS\AuthController@profile');
        Route::post('/refresh', 'CMS\AuthController@refresh');
    });

    Route::group(['prefix' => 'navigations'], function () {
        Route::get('/', 'CMS\NavigationController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\NavigationController@index');
        Route::post('/create', 'CMS\NavigationController@create');
        Route::post('/update', 'CMS\NavigationController@update');
        Route::delete('/delete', 'CMS\NavigationController@delete');
    });

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', 'CMS\CategoryController@index');
        Route::get('/download', 'CMS\CategoryController@download');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\CategoryController@index');
        Route::post('/create', 'CMS\CategoryController@create');
        Route::post('/update', 'CMS\CategoryController@update');
        Route::post('/set-status', 'CMS\CategoryController@set_status');
        Route::delete('/delete', 'CMS\CategoryController@delete');
    });

    Route::group(['prefix' => 'suppliers'], function () {
        Route::get('/', 'CMS\SupplierController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\SupplierController@index');
        Route::post('/create', 'CMS\SupplierController@create');
        Route::post('/update', 'CMS\SupplierController@update');
        Route::post('/set-status', 'CMS\SupplierController@set_status');
        Route::delete('/delete', 'CMS\SupplierController@delete');
    });

    Route::group(['prefix' => 'approval-settings'], function () {
        Route::get('/', 'CMS\ApprovalSettingController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\ApprovalSettingController@index');
        Route::post('/x-create', 'CMS\ApprovalSettingController@create');
        Route::post('/update', 'CMS\ApprovalSettingController@update');
        Route::post('/x-set-status', 'CMS\ApprovalSettingController@set_status');
        Route::delete('/x-delete', 'CMS\ApprovalSettingController@delete');
    });

    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', 'CMS\RoleController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\RoleController@index');
        Route::post('/create', 'CMS\RoleController@create');
        Route::post('/update', 'CMS\RoleController@update');
        Route::post('/set-status', 'CMS\RoleController@set_status');
        Route::delete('/delete', 'CMS\RoleController@delete');
    });

    Route::group(['prefix' => 'purchase-orders'], function () {
        Route::get('/', 'CMS\PurchaseOrderController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\PurchaseOrderController@index');
        Route::post('/create', 'CMS\PurchaseOrderController@create');
        Route::post('/update', 'CMS\PurchaseOrderController@update');
        Route::post('/approve', 'CMS\PurchaseOrderController@approve');
        Route::post('/reject', 'CMS\PurchaseOrderController@reject');
        Route::delete('/delete', 'CMS\PurchaseOrderController@delete');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'CMS\UserController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\UserController@index');
        Route::post('/create', 'CMS\UserController@create');
        Route::post('/update', 'CMS\UserController@update');
        Route::post('/set-status', 'CMS\UserController@set_status');
        Route::delete('/delete', 'CMS\UserController@delete');
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', 'CMS\NotificationController@index');
        Route::get('/count', 'CMS\NotificationController@count');
        Route::post('/read', 'CMS\NotificationController@read');
        Route::post('/delete', 'CMS\NotificationController@delete');
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', 'CMS\DashboardController@index');
    });

    Route::group(['prefix' => 'po-sap'], function () {
        Route::get('/', 'CMS\POSapController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\POSapController@index');
        Route::post('/activate', 'CMS\POSapController@activate');
        Route::delete('/delete', 'CMS\POSapController@delete');
    });

    Route::group(['prefix' => 'sub-categories'], function () {
        Route::get('/', 'CMS\SubCategoryController@index');
        Route::get('/download', 'CMS\SubCategoryController@download');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\SubCategoryController@index');
        Route::post('/create', 'CMS\SubCategoryController@create');
        Route::post('/update', 'CMS\SubCategoryController@update');
        Route::post('/set-status', 'CMS\SubCategoryController@set_status');
        Route::delete('/delete', 'CMS\SubCategoryController@delete');
    });

    Route::group(['prefix' => 'asset-controller'], function () {
        Route::get('/', 'CMS\UserAssetCtrlController@index');
        Route::post('/create', 'CMS\UserAssetCtrlController@create');
        Route::post('/update', 'CMS\UserAssetCtrlController@update');
        Route::delete('/delete', 'CMS\UserAssetCtrlController@delete');
    });

    Route::group(['prefix' => 'companies'], function () {
        Route::get('/', 'CMS\CompanyController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\CompanyController@index');
        Route::post('/create', 'CMS\CompanyController@create');
        Route::post('/update', 'CMS\CompanyController@update');
        Route::post('/set-status', 'CMS\CompanyController@set_status');
        Route::delete('/delete', 'CMS\CompanyController@delete');
    });
});
