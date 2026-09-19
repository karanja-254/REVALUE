<?php

use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\CharityNeedController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationClaimController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PaystackCallbackController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/marketplace', [ListingController::class, 'index'])->name('listings.index');
Route::get('/charities', [OrganizationController::class, 'index'])->name('organizations.index');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/my-listings', [ListingController::class, 'mine'])->name('listings.mine');

    Route::get('/charities/apply', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/charities/apply', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::get('/charity-needs/create', [CharityNeedController::class, 'create'])->name('charity-needs.create');
    Route::post('/charity-needs', [CharityNeedController::class, 'store'])->name('charity-needs.store');

    Route::post('/donations/{listing}/claim', [DonationClaimController::class, 'store'])->name('donations.claim');

    Route::get('/orders/{order}/review', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/orders/{order}/review', [ReviewController::class, 'store'])->name('reviews.store');

    Route::post('/listings/{listing}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/failed', [OrderController::class, 'failed'])->name('orders.failed');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/charities/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');

Route::get('/api/paystack/callback', PaystackCallbackController::class)->name('paystack.callback');
Route::post('/api/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/charities', [AdminOrganizationController::class, 'index'])->name('organizations.index');
    Route::post('/charities/{organization}/verify', [AdminOrganizationController::class, 'verify'])->name('organizations.verify');
    Route::post('/charities/{organization}/reject', [AdminOrganizationController::class, 'reject'])->name('organizations.reject');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{payout}/pay', [PayoutController::class, 'pay'])->name('payouts.pay');
    Route::post('/orders/{order}/refund', [PayoutController::class, 'refund'])->name('orders.refund');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
