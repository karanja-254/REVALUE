<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My deliveries') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        @php
                            $isBuyer = $order->viewer_role === 'buyer';
                            $isSeller = $order->viewer_role === 'seller';
                            $needsPickup = $isSeller
                                && ! $order->listing?->hasPickupLocation()
                                && in_array($order->order_status, [\App\Models\Order::STATUS_PAID, \App\Models\Order::STATUS_SCHEDULED], true);
                            $needsDelivery = $isBuyer
                                && ! $order->hasDeliveryLocation()
                                && in_array($order->order_status, [
                                    \App\Models\Order::STATUS_PAID,
                                    \App\Models\Order::STATUS_SCHEDULED,
                                    \App\Models\Order::STATUS_PICKED_UP,
                                    \App\Models\Order::STATUS_OUT_FOR_DELIVERY,
                                ], true);
                        @endphp
                        <a href="{{ route('tracking.show', $order) }}"
                           class="flex items-center justify-between px-5 py-4 hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $order->listing->title ?? 'Order #'.$order->id }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Order #{{ $order->id }} ·
                                    You are the <strong>{{ $order->viewer_role }}</strong>
                                </p>
                                @if($needsPickup || $needsDelivery)
                                    <p class="mt-1 text-xs text-amber-600 font-medium">
                                        @if($needsPickup) Share your pickup location @endif
                                        @if($needsPickup && $needsDelivery) · @endif
                                        @if($needsDelivery) Share your delivery location @endif
                                    </p>
                                @endif
                            </div>
                            <x-logistics.status-badge :status="$order->order_status" />
                        </a>
                    @empty
                        <p class="px-5 py-8 text-sm text-gray-500 text-center">
                            You have no orders to track yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
