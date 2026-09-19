<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Logistics\MapData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer/seller facing delivery tracking (README sections 8 & 9).
 */
class OrderTrackingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = Order::query()
            ->where(fn ($q) => $q
                ->where('buyer_id', $user->id)
                ->orWhereHas('listing', fn ($l) => $l->where('user_id', $user->id)))
            ->with(['listing', 'pickupStop', 'deliveryStop'])
            ->latest()
            ->get()
            ->map(function (Order $order) use ($user) {
                $order->setAttribute('viewer_role', $order->buyer_id === $user->id ? 'buyer' : 'seller');

                return $order;
            });

        return view('orders.tracking-index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeView($request, $order);

        $order->load([
            'listing.user',
            'buyer',
            'pickupStop.route.driver',
            'deliveryStop.route.driver',
        ]);

        return view('orders.tracking', [
            'order' => $order,
            'mapData' => MapData::forOrder($order),
            'mapsKey' => config('services.google_maps.key'),
            'viewerRole' => $order->buyer_id === $request->user()->id ? 'buyer' : 'seller',
        ]);
    }

    public function locations(Request $request, Order $order): JsonResponse
    {
        $this->authorizeView($request, $order);

        return response()->json(MapData::forOrder($order));
    }

    /**
     * A buyer, the seller, or any admin/logistics user may view tracking.
     */
    private function authorizeView(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isLogistics()) {
            return;
        }

        $order->loadMissing('listing');

        $isBuyer = $order->buyer_id === $user->id;
        $isSeller = $order->listing?->user_id === $user->id;

        abort_unless($isBuyer || $isSeller, 403);
    }
}
