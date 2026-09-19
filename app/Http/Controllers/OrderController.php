<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $request->user()
                ->orders()
                ->with('listing')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $order->load(['listing.user', 'buyer', 'sellerPayout', 'review']);

        abort_unless(
            $order->buyer_id === $request->user()->id
                || $order->listing->user_id === $request->user()->id
                || $request->user()->isAdmin(),
            403
        );

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    public function failed(): View
    {
        return view('orders.failed');
    }
}
