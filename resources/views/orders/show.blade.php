<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Order {{ $order->payment_reference }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-flash />

        <div class="rv-card p-6">
            <div class="flex flex-wrap gap-2">
                <x-status-badge :status="$order->payment_status" />
                <x-status-badge :status="$order->order_status" />
            </div>
            <h1 class="mt-4 text-3xl font-bold text-forest">{{ $order->listing->title }}</h1>
            <p class="mt-2 text-sm text-forest/60">Sold by {{ $order->listing->user->name }} · Bought by {{ $order->buyer->name }}</p>

            <ul class="mt-6 space-y-2 text-sm text-forest/80">
                <li class="flex justify-between"><span>Item</span><span>KSh {{ number_format((float) $order->item_price) }}</span></li>
                <li class="flex justify-between"><span>ReValue delivery</span><span>KSh {{ number_format((float) $order->delivery_fee) }}</span></li>
                <li class="flex justify-between"><span>Platform service</span><span>KSh {{ number_format((float) $order->service_fee) }}</span></li>
                <li class="flex justify-between border-t border-sand-dark pt-2 font-semibold text-forest">
                    <span>Total paid</span>
                    <span>KSh {{ number_format((float) $order->total_amount) }}</span>
                </li>
            </ul>

            @if ($order->requiresRefund())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                    Paystack charged this buyer, but another buyer already won the listing. Do not treat this as a failed payment. An admin must refund this charge.
                </div>
            @endif

            @if ($order->payment_status === 'paid')
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @if (Auth::id() === $order->listing->user_id || Auth::user()->isAdmin())
                        <div class="rounded-2xl bg-sand p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-clay">Pickup PIN</p>
                            <p class="mt-1 font-mono text-3xl font-bold text-forest">{{ $order->pickup_pin }}</p>
                            <p class="mt-1 text-xs text-forest/55">Seller gives this after ReValue takes the item.</p>
                        </div>
                    @endif
                    @if (Auth::id() === $order->buyer_id || Auth::user()->isAdmin())
                        <div class="rounded-2xl bg-sand p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-clay">Delivery PIN</p>
                            <p class="mt-1 font-mono text-3xl font-bold text-forest">{{ $order->delivery_pin }}</p>
                            <p class="mt-1 text-xs text-forest/55">Buyer gives this after the item arrives.</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($order->sellerPayout)
                <p class="mt-6 text-sm text-forest/70">
                    Seller payout: <strong class="capitalize">{{ $order->sellerPayout->status }}</strong>
                    · KSh {{ number_format((float) $order->sellerPayout->amount) }}
                    @if ($order->sellerPayout->mpesa_receipt)
                        · M-Pesa {{ $order->sellerPayout->mpesa_receipt }}
                    @endif
                </p>
            @endif

            @if (Auth::user()->isAdmin() && in_array($order->payment_status, ['paid', 'refund_required'], true))
                <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="mt-6">
                    @csrf
                    <button class="rv-btn-ghost">Mark refunded</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
