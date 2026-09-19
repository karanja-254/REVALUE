<?php

use App\Http\Controllers\ListingController;
use App\Http\Controllers\Admin\ManualReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/listings', [ListingController::class, 'store']);
    Route::get('/listings/{listing}', [ListingController::class, 'show']);
    Route::put('/listings/{listing}/accept-price', [ListingController::class, 'acceptPrice']);
    Route::put('/listings/{listing}/request-review', [ListingController::class, 'requestReview']);

    Route::prefix('admin')->group(function () {
        Route::get('/manual-reviews', [ManualReviewController::class, 'index']);
        Route::put('/manual-reviews/{review}/approve', [ManualReviewController::class, 'approve']);
    });
});
