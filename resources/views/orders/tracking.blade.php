@php
    $isBuyer = $viewerRole === 'buyer';
    $isSeller = $viewerRole === 'seller';
    $activeRoute = $order->deliveryStop?->route && $order->deliveryStop->route->isActive()
        ? $order->deliveryStop->route
        : $order->pickupStop?->route;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('tracking.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; My deliveries</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $order->listing->title ?? 'Order #'.$order->id }}
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

            {{-- Map --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-medium text-gray-900 mb-3">Live tracking</h3>
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
                @if($order->listing?->pickup_address)
                    <p>Pickup from: {{ $order->listing->pickup_address }}</p>
                @endif
                @if($order->delivery_address)
                    <p>Deliver to: {{ $order->delivery_address }}</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
