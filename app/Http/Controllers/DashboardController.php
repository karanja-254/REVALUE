<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $reviewableOrders = Order::query()
            ->with('listing')
            ->where('buyer_id', $user->id)
            ->where('order_status', Order::STATUS_COMPLETED)
            ->whereDoesntHave('review')
            ->latest()
            ->get();

        return view('dashboard', [
            'listings' => $user->listings()->latest()->take(5)->get(),
            'orders' => $user->orders()->with('listing')->latest()->take(5)->get(),
            'organization' => $user->organization,
            'reviewableOrders' => $reviewableOrders,
            'listingCount' => $user->listings()->count(),
            'availableCount' => $user->listings()->where('status', Listing::STATUS_AVAILABLE)->count(),
            'orderCount' => $user->orders()->count(),
        ]);
    }
}
