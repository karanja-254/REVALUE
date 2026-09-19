<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Organization;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sellListings = Listing::query()
            ->where('type', Listing::TYPE_SELL)
            ->where('status', Listing::STATUS_AVAILABLE)
            ->latest()
            ->take(3)
            ->get();

        return view('home', [
            'sellListings' => $sellListings,
            'charities' => Organization::query()
                ->verified()
                ->charities()
                ->with(['needs' => fn ($query) => $query->where('status', 'open')])
                ->latest()
                ->take(3)
                ->get(),
            'availableCount' => Listing::query()->where('status', Listing::STATUS_AVAILABLE)->count(),
            'charityCount' => Organization::query()->verified()->charities()->count(),
        ]);
    }
}
