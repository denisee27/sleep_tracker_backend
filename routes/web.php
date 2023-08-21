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
        Route::post('/profile-pic', 'CMS\AuthController@foto_profile');
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

    Route::group(['prefix' => 'materials'], function () {
        Route::get('/', 'CMS\MaterialController@index');
        Route::get('/download', 'CMS\MaterialController@download');
        Route::get('/stocks/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialController@stocks');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialController@index');
        Route::post('/create', 'CMS\MaterialController@create');
        Route::post('/update', 'CMS\MaterialController@update');
        Route::post('/set-status', 'CMS\MaterialController@set_status');
        Route::delete('/delete', 'CMS\MaterialController@delete');
    });

    Route::group(['prefix' => 'projects'], function () {
        Route::get('/', 'CMS\ProjectController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\ProjectController@index');
        Route::post('/create', 'CMS\ProjectController@create');
        Route::post('/update', 'CMS\ProjectController@update');
        Route::post('/set-status', 'CMS\ProjectController@set_status');
        Route::delete('/delete', 'CMS\ProjectController@delete');
    });

    Route::group(['prefix' => 'companies'], function () {
        Route::get('/', 'CMS\CompanyController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\CompanyController@index');
        Route::post('/create', 'CMS\CompanyController@create');
        Route::post('/update', 'CMS\CompanyController@update');
        Route::post('/set-status', 'CMS\CompanyController@set_status');
        Route::delete('/delete', 'CMS\CompanyController@delete');
    });

    Route::group(['prefix' => 'suppliers'], function () {
        Route::get('/', 'CMS\SupplierController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\SupplierController@index');
        Route::post('/create', 'CMS\SupplierController@create');
        Route::post('/update', 'CMS\SupplierController@update');
        Route::post('/set-status', 'CMS\SupplierController@set_status');
        Route::delete('/delete', 'CMS\SupplierController@delete');
    });

    Route::group(['prefix' => 'main-warehouses'], function () {
        Route::get('/', 'CMS\MainWarehouseController@index');
        Route::get('/download', 'CMS\MainWarehouseController@download');
        Route::get('/stocks/{id:[a-zA-Z-?0-9]+}', 'CMS\MainWarehouseController@stocks');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\MainWarehouseController@index');
        Route::post('/create', 'CMS\MainWarehouseController@create');
        Route::post('/update', 'CMS\MainWarehouseController@update');
        Route::post('/set-status', 'CMS\MainWarehouseController@set_status');
        Route::delete('/delete', 'CMS\MainWarehouseController@delete');
    });

    Route::group(['prefix' => 'transit-warehouses'], function () {
        Route::get('/', 'CMS\TransitWarehouseController@index');
        Route::get('/download', 'CMS\TransitWarehouseController@download');
        Route::get('/stocks/{id:[a-zA-Z-?0-9]+}', 'CMS\TransitWarehouseController@stocks');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\TransitWarehouseController@index');
        Route::post('/create', 'CMS\TransitWarehouseController@create');
        Route::post('/update', 'CMS\TransitWarehouseController@update');
        Route::post('/set-status', 'CMS\TransitWarehouseController@set_status');
        Route::delete('/delete', 'CMS\TransitWarehouseController@delete');
    });

    Route::group(['prefix' => 'lastmile-warehouses'], function () {
        Route::get('/', 'CMS\LastmileWarehouseController@index');
        Route::get('/download', 'CMS\LastmileWarehouseController@download');
        Route::get('/stocks/{id:[a-zA-Z-?0-9]+}', 'CMS\LastmileWarehouseController@stocks');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\LastmileWarehouseController@index');
        Route::post('/create', 'CMS\LastmileWarehouseController@create');
        Route::post('/update', 'CMS\LastmileWarehouseController@update');
        Route::post('/set-status', 'CMS\LastmileWarehouseController@set_status');
        Route::delete('/delete', 'CMS\LastmileWarehouseController@delete');
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

    Route::group(['prefix' => 'positions'], function () {
        Route::get('/', 'CMS\JobPositionController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\JobPositionController@index');
        Route::post('/create', 'CMS\JobPositionController@create');
        Route::post('/update', 'CMS\JobPositionController@update');
        Route::post('/set-status', 'CMS\JobPositionController@set_status');
        Route::delete('/delete', 'CMS\JobPositionController@delete');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'CMS\UserController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\UserController@index');
        Route::post('/create', 'CMS\UserController@create');
        Route::post('/update', 'CMS\UserController@update');
        Route::post('/set-status', 'CMS\UserController@set_status');
        Route::delete('/delete', 'CMS\UserController@delete');
    });

    Route::group(['prefix' => 'supplier-inbound'], function () {
        Route::get('/', 'CMS\SupplierInboundController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\SupplierInboundController@index');
        Route::post('/create', 'CMS\SupplierInboundController@create');
        Route::post('/update', 'CMS\SupplierInboundController@update');
        Route::post('/approve', 'CMS\SupplierInboundController@approve');
        Route::post('/reject', 'CMS\SupplierInboundController@reject');
        Route::post('/receive', 'CMS\SupplierInboundController@receive');
        Route::delete('/delete', 'CMS\SupplierInboundController@delete');
    });

    Route::group(['prefix' => 'transfer-material'], function () {
        Route::get('/', 'CMS\TransferMaterialController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\TransferMaterialController@index');
        Route::post('/create', 'CMS\TransferMaterialController@create');
        Route::post('/update', 'CMS\TransferMaterialController@update');
        Route::post('/approve', 'CMS\TransferMaterialController@approve');
        Route::post('/reject', 'CMS\TransferMaterialController@reject');
        Route::post('/receive', 'CMS\TransferMaterialController@receive');
        Route::delete('/delete', 'CMS\TransferMaterialController@delete');
    });

    Route::group(['prefix' => 'material-stocks'], function () {
        Route::get('/', 'CMS\MaterialStockController@index');
        Route::post('/reduce-fifo', 'CMS\MaterialStockController@reduce_fifo');
    });

    Route::get('trouble-tickets', 'CMS\TroubleTicketController@index');

    Route::group(['prefix' => 'material-to-site'], function () {
        Route::get('/', 'CMS\MaterialToSiteController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialToSiteController@index');
        Route::post('/create', 'CMS\MaterialToSiteController@create');
        Route::post('/update', 'CMS\MaterialToSiteController@update');
        Route::post('/approve', 'CMS\MaterialToSiteController@approve');
        Route::post('/reject', 'CMS\MaterialToSiteController@reject');
        Route::post('/receive', 'CMS\MaterialToSiteController@receive');
        Route::post('/confirm', 'CMS\MaterialToSiteController@confirm');
        Route::post('/close', 'CMS\MaterialToSiteController@close');
        Route::post('/upload-photo', 'CMS\MaterialToSiteController@upload_photo');
        Route::delete('/delete', 'CMS\MaterialToSiteController@delete');
        Route::delete('/remove-photo', 'CMS\MaterialToSiteController@remove_photo');
    });

    Route::group(['prefix' => 'transfer-project-code'], function () {
        Route::get('/', 'CMS\TransferProjectCodeController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\TransferProjectCodeController@index');
        Route::post('/create', 'CMS\TransferProjectCodeController@create');
        Route::post('/update', 'CMS\TransferProjectCodeController@update');
        Route::post('/approve', 'CMS\TransferProjectCodeController@approve');
        Route::post('/reject', 'CMS\TransferProjectCodeController@reject');
        Route::delete('/delete', 'CMS\TransferProjectCodeController@delete');
    });

    Route::group(['prefix' => 'stock-opname'], function () {
        Route::get('/', 'CMS\StockOpnameController@index');
        Route::get('/download/{id:[a-zA-Z-?0-9]+}', 'CMS\StockOpnameController@download');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\StockOpnameController@index');
        Route::post('/create', 'CMS\StockOpnameController@create');
        Route::post('/submit', 'CMS\StockOpnameController@submit');
        Route::post('/update', 'CMS\StockOpnameController@update');
        Route::post('/approve', 'CMS\StockOpnameController@approve');
        Route::post('/reject', 'CMS\StockOpnameController@reject');
        Route::post('/upload', 'CMS\StockOpnameController@upload');
        Route::delete('/delete', 'CMS\StockOpnameController@delete');
    });

    Route::group(['prefix' => 'material-disposal'], function () {
        Route::get('/', 'CMS\MaterialDisposalController@index');
        Route::get('/download/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialDisposalController@download');
        Route::get('/download-attachment/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialDisposalController@download_attachment');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialDisposalController@index');
        Route::post('/create', 'CMS\MaterialDisposalController@create');
        Route::post('/upload', 'CMS\MaterialDisposalController@upload');
        Route::delete('/delete', 'CMS\MaterialDisposalController@delete');
    });

    Route::group(['prefix' => 'material-discrepancies'], function () {
        Route::get('/', 'CMS\MaterialDiscrepancyController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\MaterialDiscrepancyController@index');
        Route::post('/update', 'CMS\MaterialDiscrepancyController@update');
        Route::post('/approve', 'CMS\MaterialDiscrepancyController@approve');
        Route::post('/reject', 'CMS\MaterialDiscrepancyController@reject');
        Route::delete('/delete', 'CMS\MaterialDiscrepancyController@delete');
    });

    Route::group(['prefix' => 'used-material-returns'], function () {
        Route::get('/', 'CMS\UsedMaterialReturnController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\UsedMaterialReturnController@index');
        Route::post('/create', 'CMS\UsedMaterialReturnController@create');
        Route::post('/update', 'CMS\UsedMaterialReturnController@update');
        Route::post('/approve', 'CMS\UsedMaterialReturnController@approve');
        Route::post('/reject', 'CMS\UsedMaterialReturnController@reject');
        Route::delete('/delete', 'CMS\UsedMaterialReturnController@delete');
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', 'CMS\NotificationController@index');
        Route::get('/count', 'CMS\NotificationController@count');
        Route::post('/read', 'CMS\NotificationController@read');
        Route::post('/delete', 'CMS\NotificationController@delete');
    });

    Route::group(['prefix' => 'report-material-stock'], function () {
        Route::post('/', 'CMS\ReportMaterialStockController@view');
        Route::get('/download', 'CMS\ReportMaterialStockController@download');
    });

    Route::group(['prefix' => 'report-stock-alert'], function () {
        Route::post('/', 'CMS\ReportStockAlertController@view');
        Route::get('/download', 'CMS\ReportStockAlertController@download');
    });

    Route::group(['prefix' => 'report-transaction'], function () {
        Route::post('/', 'CMS\ReportTransactionController@view');
        Route::get('/download', 'CMS\ReportTransactionController@download');
    });

    Route::group(['prefix' => 'report-site'], function () {
        Route::post('/', 'CMS\ReportSiteController@view');
        Route::get('/download', 'CMS\ReportSiteController@download');
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', 'CMS\DashboardController@index');
        Route::get('/finance', 'CMS\DashboardFinanceController@index');
        Route::get('/project', 'CMS\DashboardProjectController@index');
        Route::get('/warehouse', 'CMS\DashboardWarehouseController@index');
    });

    Route::group(['prefix' => 'po-sap'], function () {
        Route::get('/', 'CMS\POSapController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\POSapController@index');
        Route::post('/activate', 'CMS\POSapController@activate');
        Route::delete('/delete', 'CMS\POSapController@delete');
    });

    Route::group(['prefix' => 'stock-alert-notifications'], function () {
        Route::get('/', 'CMS\StockAlertNotificationController@index');
        Route::get('/{id:[a-zA-Z-?0-9]+}', 'CMS\StockAlertNotificationController@index');
        Route::post('/create', 'CMS\StockAlertNotificationController@create');
        Route::post('/update', 'CMS\StockAlertNotificationController@update');
        Route::post('/set-status', 'CMS\StockAlertNotificationController@set_status');
        Route::delete('/delete', 'CMS\StockAlertNotificationController@delete');
    });
});
