<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">My orders</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="rv-card flex items-center justify-between p-4">
                    <div>
                        <p class="font-semibold text-forest">{{ $order->listing->title }}</p>
                        <p class="text-xs capitalize text-forest/55">{{ str_replace('_', ' ', $order->order_status) }} · {{ $order->payment_status }} · KSh {{ number_format((float) $order->total_amount) }}</p>
                    </div>
                    <x-status-badge :status="$order->payment_status" />
                </a>
            @empty
                <div class="rv-card p-8 text-sm text-forest/70">No purchases yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $orders->links() }}</div>
    </div>
</x-app-layout>
