<?php

namespace App\Http\Controllers;

use App\Http\Requests\Logistics\UpdateDeliveryLocationRequest;
use App\Http\Requests\Logistics\UpdatePickupLocationRequest;
use App\Models\Order;
use App\Support\Logistics\MapData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     * Seller pins where the driver should collect the item.
     */
    public function updatePickupLocation(UpdatePickupLocationRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeSeller($request, $order);
        $this->assertPickupLocationEditable($order);

        $order->listing->update([
            'pickup_address' => $request->input('address'),
            'pickup_latitude' => $request->input('latitude'),
            'pickup_longitude' => $request->input('longitude'),
            'pickup_notes' => $request->input('notes'),
        ]);

        return back()->with('location_status', 'Pickup location saved. The driver can now navigate to you.');
    }

    /**
     * Buyer pins where the driver should deliver the item.
     */
    public function updateDeliveryLocation(UpdateDeliveryLocationRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeBuyer($request, $order);
        $this->assertDeliveryLocationEditable($order);

        $order->update([
            'delivery_address' => $request->input('address'),
            'delivery_latitude' => $request->input('latitude'),
            'delivery_longitude' => $request->input('longitude'),
            'delivery_notes' => $request->input('notes'),
        ]);

        return back()->with('location_status', 'Delivery location saved. The driver can now navigate to you.');
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

    private function authorizeSeller(Request $request, Order $order): void
    {
        $order->loadMissing('listing');

        abort_unless($order->listing?->user_id === $request->user()->id, 403);
    }

    private function authorizeBuyer(Request $request, Order $order): void
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);
    }

    private function assertPickupLocationEditable(Order $order): void
    {
        abort_unless(in_array($order->order_status, [
            Order::STATUS_PAID,
            Order::STATUS_SCHEDULED,
        ], true), 403, 'Pickup location can no longer be changed for this order.');
    }

    private function assertDeliveryLocationEditable(Order $order): void
    {
        abort_unless(in_array($order->order_status, [
            Order::STATUS_PAID,
            Order::STATUS_SCHEDULED,
            Order::STATUS_PICKED_UP,
            Order::STATUS_OUT_FOR_DELIVERY,
        ], true), 403, 'Delivery location can no longer be changed for this order.');
    }
}
