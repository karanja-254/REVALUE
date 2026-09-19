@php
    $isBuyer = $viewerRole === 'buyer';
    $isSeller = $viewerRole === 'seller';
    $listing = $order->listing;
    $activeRoute = $order->deliveryStop?->route && $order->deliveryStop->route->isActive()
        ? $order->deliveryStop->route
        : $order->pickupStop?->route;

    $canSharePickup = $isSeller && in_array($order->order_status, [
        \App\Models\Order::STATUS_PAID,
        \App\Models\Order::STATUS_SCHEDULED,
    ], true);

    $canShareDelivery = $isBuyer && in_array($order->order_status, [
        \App\Models\Order::STATUS_PAID,
        \App\Models\Order::STATUS_SCHEDULED,
        \App\Models\Order::STATUS_PICKED_UP,
        \App\Models\Order::STATUS_OUT_FOR_DELIVERY,
    ], true);

    $defaultCenter = config('services.google_maps.default_center');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('tracking.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; My deliveries</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $listing->title ?? 'Order #'.$order->id }}
                </h2>
            </div>
            <x-logistics.status-badge :status="$order->order_status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Progress --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <x-logistics.progress-steps :steps="$order->trackingSteps()" />

                @if($order->order_status === \App\Models\Order::STATUS_PICKUP_FAILED)
                    <p class="mt-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        Pickup failed — the item did not match the listing. A refund will be processed.
                    </p>
                @endif
            </div>

            {{-- Seller: share pickup location --}}
            @if($canSharePickup)
                <x-logistics.location-picker
                    type="pickup"
                    :action="route('tracking.pickup-location', $order)"
                    :address="$listing->pickup_address ?? ''"
                    :latitude="$listing->pickup_latitude"
                    :longitude="$listing->pickup_longitude"
                    :notes="$listing->pickup_notes ?? ''"
                    :maps-key="$mapsKey"
                    :default-lat="$defaultCenter['lat']"
                    :default-lng="$defaultCenter['lng']"
                    class="shadow-sm"
                />
            @endif

            {{-- Buyer: share delivery location --}}
            @if($canShareDelivery)
                <x-logistics.location-picker
                    type="delivery"
                    :action="route('tracking.delivery-location', $order)"
                    :address="$order->delivery_address ?? ''"
                    :latitude="$order->delivery_latitude"
                    :longitude="$order->delivery_longitude"
                    :notes="$order->delivery_notes ?? ''"
                    :maps-key="$mapsKey"
                    :default-lat="$defaultCenter['lat']"
                    :default-lng="$defaultCenter['lng']"
                    class="shadow-sm"
                />
            @endif

            {{-- PIN card (only the relevant party sees their PIN) --}}
            @php
                $showPickupPin = $isSeller && $order->pickup_pin
                    && in_array($order->order_status, [\App\Models\Order::STATUS_PAID, \App\Models\Order::STATUS_SCHEDULED], true);
                $showDeliveryPin = $isBuyer && $order->delivery_pin
                    && in_array($order->order_status, [\App\Models\Order::STATUS_PICKED_UP, \App\Models\Order::STATUS_OUT_FOR_DELIVERY], true);
            @endphp

            @if($showPickupPin || $showDeliveryPin)
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 text-center">
                    <p class="text-sm text-indigo-700">
                        {{ $showPickupPin ? 'Your pickup PIN' : 'Your delivery PIN' }}
                    </p>
                    <p class="mt-1 text-4xl font-mono font-bold tracking-[0.4em] text-indigo-900">
                        {{ $showPickupPin ? $order->pickup_pin : $order->delivery_pin }}
                    </p>
                    <p class="mt-2 text-xs text-indigo-600 max-w-md mx-auto">
                        @if($showPickupPin)
                            Give this to the ReValue driver <strong>only after</strong> they've checked the item and taken it.
                        @else
                            Give this to the driver when your item is delivered to complete the order.
                        @endif
                    </p>
                </div>
            @endif

            {{-- Live tracking map (shows both pins once shared) --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-medium text-gray-900 mb-1">Live tracking</h3>
                <p class="text-sm text-gray-500 mb-3">
                    @if($listing?->hasPickupLocation() && $order->hasDeliveryLocation())
                        Seller pickup and buyer delivery locations are on the map.
                    @elseif($listing?->hasPickupLocation())
                        Pickup location is set. @if($canShareDelivery) Share your delivery location above. @endif
                    @elseif($order->hasDeliveryLocation())
                        Delivery location is set. Waiting for the seller's pickup location.
                    @else
                        Share your location above to appear on this map.
                    @endif
                </p>
                <x-logistics.map
                    :data="$mapData"
                    :maps-key="$mapsKey"
                    :locations-url="route('tracking.locations', $order)"
                    height="h-80" />
                @if($activeRoute)
                    <p class="mt-3 text-sm text-gray-500">
                        Driver: <strong>{{ $activeRoute->driver?->name ?? 'To be assigned' }}</strong>
                        · Collection day: {{ $activeRoute->collection_date->format('l, M j') }}
                    </p>
                @else
                    <p class="mt-3 text-sm text-gray-500">
                        Not yet scheduled. Pickups &amp; deliveries run on Wednesdays and Saturdays.
                    </p>
                @endif
            </div>

            {{-- Details --}}
            <div class="bg-white rounded-lg shadow-sm p-5 text-sm text-gray-600 space-y-1">
                <p>Order total: <strong>KSh {{ number_format((float) $order->total_amount, 2) }}</strong></p>
                <p>Payment status: <x-logistics.status-badge :status="$order->payment_status" /></p>
                @if($listing?->pickup_address)
                    <p>Pickup from: {{ $listing->pickup_address }}
                        @if($listing->pickup_notes) <span class="text-gray-400">({{ $listing->pickup_notes }})</span> @endif
                    </p>
                @elseif($canSharePickup)
                    <p class="text-amber-600">Pickup location not shared yet.</p>
                @endif
                @if($order->delivery_address)
                    <p>Deliver to: {{ $order->delivery_address }}
                        @if($order->delivery_notes) <span class="text-gray-400">({{ $order->delivery_notes }})</span> @endif
                    </p>
                @elseif($canShareDelivery)
                    <p class="text-amber-600">Delivery location not shared yet.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
