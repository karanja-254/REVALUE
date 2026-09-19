<?php

namespace App\Providers;

use App\Models\Listing;
use App\Models\ManualReview;
use App\Policies\ListingPolicy;
use App\Policies\ManualReviewPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Listing::class => ListingPolicy::class,
        ManualReview::class => ManualReviewPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
