<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverModuleController;
use App\Http\Controllers\Api\FinanceClientController;
use App\Http\Controllers\Api\FinanceQuoteController;
use App\Http\Controllers\Api\FinanceRouteController;
use App\Http\Controllers\Api\FuelController;
use App\Http\Controllers\Api\OperationBusController;
use App\Http\Controllers\Api\OperationMaintenanceController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    Route::get('/driver-catalogs', [DriverModuleController::class, 'catalogs'])
        ->middleware('permission:drivers.view');

    Route::get('/drivers', [DriverController::class, 'index'])->middleware('permission:drivers.view');
    Route::get('/drivers/{driver}', [DriverController::class, 'show'])->middleware('permission:drivers.view');
    Route::post('/drivers', [DriverController::class, 'store'])->middleware('permission:drivers.create');
    Route::put('/drivers/{driver}', [DriverController::class, 'update'])->middleware('permission:drivers.update');
    Route::patch('/drivers/{driver}', [DriverController::class, 'update'])->middleware('permission:drivers.update');
    Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->middleware('permission:drivers.delete');
    Route::post('/drivers/{driver}/photo', [DriverController::class, 'uploadPhoto'])->middleware('permission:drivers.photo');
    Route::post('/drivers/{driver}/documents', [DriverModuleController::class, 'storeDocument'])->middleware('permission:drivers.documents.manage');
    Route::post('/drivers/{driver}/emergency-contacts', [DriverModuleController::class, 'storeEmergencyContact'])->middleware('permission:drivers.update');
    Route::post('/drivers/{driver}/medical-leaves', [DriverModuleController::class, 'storeMedicalLeave'])->middleware('permission:drivers.leaves.manage');
    Route::post('/drivers/{driver}/conduct-reports', [DriverModuleController::class, 'storeConductReport'])->middleware('permission:drivers.conduct.manage');
    Route::post('/drivers/{driver}/termination-records', [DriverModuleController::class, 'storeTerminationRecord'])->middleware('permission:drivers.termination.manage');
    Route::post('/drivers/{driver}/rehire', [DriverModuleController::class, 'rehire'])->middleware('permission:drivers.termination.manage');
    Route::post('/drivers/{driver}/traffic-fine-checks', [DriverModuleController::class, 'storeTrafficFineCheck'])->middleware('permission:drivers.conduct.manage');

    Route::get('/finance-clients/history', [FinanceClientController::class, 'history'])->middleware('permission:finance.clients.view');
    Route::get('/finance-clients', [FinanceClientController::class, 'index'])->middleware('permission:finance.clients.view');
    Route::post('/finance-clients', [FinanceClientController::class, 'store'])->middleware('permission:finance.clients.manage');
    Route::put('/finance-clients/{financeClient}', [FinanceClientController::class, 'update'])->middleware('permission:finance.clients.manage');
    Route::delete('/finance-clients/{financeClient}', [FinanceClientController::class, 'destroy'])->middleware('permission:finance.clients.manage');

    Route::get('/finance-routes/history', [FinanceRouteController::class, 'history'])->middleware('permission:finance.routes.view');
    Route::get('/finance-routes', [FinanceRouteController::class, 'index'])->middleware('permission:finance.routes.view');
    Route::post('/finance-routes', [FinanceRouteController::class, 'store'])->middleware('permission:finance.routes.manage');
    Route::put('/finance-routes/{financeRoute}', [FinanceRouteController::class, 'update'])->middleware('permission:finance.routes.manage');
    Route::delete('/finance-routes/{financeRoute}', [FinanceRouteController::class, 'destroy'])->middleware('permission:finance.routes.manage');

    Route::get('/finance-quotes', [FinanceQuoteController::class, 'index'])->middleware('permission:finance.quotes.view');
    Route::post('/finance-quotes', [FinanceQuoteController::class, 'store'])->middleware('permission:finance.quotes.manage');
    Route::put('/finance-quotes/{financeQuote}', [FinanceQuoteController::class, 'update'])->middleware('permission:finance.quotes.manage');

    Route::get('/operation-buses/history', [OperationBusController::class, 'history'])->middleware('permission:operations.buses.history.view');
    Route::get('/operation-buses', [OperationBusController::class, 'index'])->middleware('permission:operations.buses.view');
    Route::post('/operation-buses', [OperationBusController::class, 'store'])->middleware('permission:operations.buses.create');
    Route::put('/operation-buses/{operationBus}', [OperationBusController::class, 'update'])->middleware('permission:operations.buses.update');
    Route::patch('/operation-buses/{operationBus}/status', [OperationBusController::class, 'updateStatus'])->middleware('permission:operations.buses.status.update');
    Route::patch('/operation-buses/{operationBus}/mileage', [OperationBusController::class, 'updateMileage'])->middleware('permission:operations.buses.mileage.update');
    Route::patch('/operation-buses/{operationBus}/driver', [OperationBusController::class, 'assignDriver'])->middleware('permission:operations.buses.driver.assign');
    Route::delete('/operation-buses/{operationBus}', [OperationBusController::class, 'destroy'])->middleware('permission:operations.buses.retire');
    Route::post('/operation-buses/{operationBus}/photo', [OperationBusController::class, 'uploadPhoto'])->middleware('permission:operations.buses.photo');
    Route::get('/operation-maintenance-catalogs', [OperationMaintenanceController::class, 'catalogs'])->middleware('permission:operations.maintenance.view');
    Route::get('/operation-maintenances', [OperationMaintenanceController::class, 'index'])->middleware('permission:operations.maintenance.view');
    Route::post('/operation-maintenances', [OperationMaintenanceController::class, 'store'])->middleware('permission:operations.maintenance.create');

    Route::get('/fuel/dashboard', [FuelController::class, 'dashboard'])->middleware('permission:fuel.view');
    Route::get('/fuel/catalogs', [FuelController::class, 'catalogs'])->middleware('permission:fuel.view');
    Route::get('/fuel-records', [FuelController::class, 'index'])->middleware('permission:fuel.view');
    Route::post('/fuel-tanks', [FuelController::class, 'storeTank'])->middleware('permission:fuel.settings.manage');
    Route::post('/fuel-hoses', [FuelController::class, 'storeHose'])->middleware('permission:fuel.settings.manage');
    Route::post('/fuel-partners', [FuelController::class, 'storePartner'])->middleware('permission:fuel.partners.manage');
    Route::put('/fuel-partners/{fuelPartner}', [FuelController::class, 'updatePartner'])->middleware('permission:fuel.partners.manage');
    Route::post('/fuel-purchases', [FuelController::class, 'storePurchase'])->middleware('permission:fuel.purchases.manage');
    Route::put('/fuel-purchases/{fuelPurchase}', [FuelController::class, 'updatePurchase'])->middleware('permission:fuel.purchases.manage');
    Route::post('/fuel-dispatches', [FuelController::class, 'storeDispatch'])->middleware('permission:fuel.dispatches.manage');
    Route::put('/fuel-dispatches/{fuelDispatch}', [FuelController::class, 'updateDispatch'])->middleware('permission:fuel.dispatches.manage');
    Route::patch('/fuel-dispatches/{fuelDispatch}/void', [FuelController::class, 'voidDispatch'])->middleware('permission:fuel.dispatches.void');
    Route::post('/fuel-measurements', [FuelController::class, 'storeMeasurement'])->middleware('permission:fuel.measurements.manage');
    Route::put('/fuel-measurements/{fuelTankMeasurement}', [FuelController::class, 'updateMeasurement'])->middleware('permission:fuel.measurements.manage');
    Route::post('/fuel-closures', [FuelController::class, 'storeClosure'])->middleware('permission:fuel.closures.manage');
    Route::put('/fuel-closures/{fuelDailyClosure}', [FuelController::class, 'updateClosure'])->middleware('permission:fuel.closures.manage');
    Route::post('/fuel-adjustments', [FuelController::class, 'storeAdjustment'])->middleware('permission:fuel.adjustments.manage');
    Route::put('/fuel-adjustments/{fuelAdjustment}', [FuelController::class, 'updateAdjustment'])->middleware('permission:fuel.adjustments.manage');

    Route::get('/users', [UserManagementController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserManagementController::class, 'store'])->middleware('permission:users.manage');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.manage');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:users.manage');
    Route::put('/roles/{role}', [UserManagementController::class, 'updateRole'])->middleware('permission:roles.manage');
});
