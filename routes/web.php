<?php

use App\Http\Controllers\Logistics\DriverLocationController;
use App\Http\Controllers\Logistics\LogisticsDashboardController;
use App\Http\Controllers\Logistics\RouteController;
use App\Http\Controllers\Logistics\RouteStopController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Delivery tracking (buyers & sellers)  — Maps/Logistics, Person 4
|--------------------------------------------------------------------------
| Any authenticated user can track orders they are the buyer or seller of.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tracking', [OrderTrackingController::class, 'index'])->name('tracking.index');
    Route::get('/tracking/{order}', [OrderTrackingController::class, 'show'])->name('tracking.show');
    Route::get('/tracking/{order}/locations', [OrderTrackingController::class, 'locations'])->name('tracking.locations');
    Route::post('/tracking/{order}/pickup-location', [OrderTrackingController::class, 'updatePickupLocation'])->name('tracking.pickup-location');
    Route::post('/tracking/{order}/delivery-location', [OrderTrackingController::class, 'updateDeliveryLocation'])->name('tracking.delivery-location');
});

/*
|--------------------------------------------------------------------------
| Logistics operations (drivers & admins)  — Maps/Logistics, Person 4
|--------------------------------------------------------------------------
| Restricted to the logistics and admin roles (super admins pass through the
| role middleware automatically).
*/
Route::middleware(['auth', 'verified', 'role:logistics,admin'])
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function () {
        Route::get('/', [LogisticsDashboardController::class, 'index'])->name('dashboard');

        // Driver GPS reporting.
        Route::post('/driver/location', [DriverLocationController::class, 'store'])->name('driver.location');

        // Routes (collection runs).
        Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
        Route::get('/routes/{route}', [RouteController::class, 'show'])->name('routes.show');
        Route::get('/routes/{route}/locations', [DriverLocationController::class, 'routeLocations'])->name('routes.locations');
        Route::post('/routes/{route}/start', [RouteController::class, 'start'])->name('routes.start');
        Route::post('/routes/{route}/complete', [RouteController::class, 'complete'])->name('routes.complete');

        // Individual stops (status + two-PIN verification).
        Route::patch('/stops/{stop}/status', [RouteStopController::class, 'updateStatus'])->name('stops.status');
        Route::post('/stops/{stop}/verify-pickup', [RouteStopController::class, 'verifyPickup'])->name('stops.verify-pickup');
        Route::post('/stops/{stop}/verify-delivery', [RouteStopController::class, 'verifyDelivery'])->name('stops.verify-delivery');
    });

require __DIR__.'/auth.php';
