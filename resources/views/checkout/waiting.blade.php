<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Check your phone</h2>
    </x-slot>

    <div class="mx-auto max-w-xl px-4 py-10">
        <div class="rv-card p-6 text-center" x-data="mpesaWait()" x-init="poll()">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">M-PESA</p>
            <h3 class="mt-2 font-bold text-2xl text-forest">Check your phone</h3>

            @if (! empty($mpesa['phone']))
                <p class="mt-2 text-sm text-forest/70">We sent an M-PESA prompt to {{ $mpesa['phone'] }}.</p>
            @else
                <p class="mt-2 text-sm text-forest/70">We sent an M-PESA prompt to your phone.</p>
            @endif

            <p class="mt-1 text-sm text-forest/70">
                {{ $mpesa['display_text'] ?? 'Enter your M-PESA PIN on your phone to complete payment.' }}
            </p>

            <div class="mt-6 rounded-2xl bg-sand p-4 text-left text-sm text-forest/75">
                <div class="flex justify-between"><span>{{ $order->listing->title }}</span><span>KSh {{ number_format((float) $order->item_price) }}</span></div>
                <div class="mt-1 flex justify-between"><span>ReValue delivery</span><span>KSh {{ number_format((float) $order->delivery_fee) }}</span></div>
                <div class="mt-1 flex justify-between"><span>Platform service</span><span>KSh {{ number_format((float) $order->service_fee) }}</span></div>
                <div class="mt-2 flex justify-between border-t border-forest/10 pt-2 font-semibold text-forest">
                    <span>Total</span><span>KSh {{ number_format((float) $order->total_amount) }}</span>
                </div>
            </div>

            <p class="mt-6 text-sm font-medium text-forest" x-show="state === 'waiting'">Waiting for M-PESA confirmation…</p>
            <p class="mt-6 text-sm font-medium text-rose-700" x-show="state === 'failed'" x-cloak>Payment was not completed. Try again.</p>

            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('listings.show', $order->listing) }}" class="rv-btn-ghost" x-show="state === 'failed'" x-cloak>Back to the item</a>
                <button type="button" class="text-sm text-forest/60 underline" @click="poll()" x-show="state === 'waiting'">Check now</button>
            </div>

            <p class="mt-6 text-xs text-forest/45">Never type your M-PESA PIN on this website. Only enter it in the official M-PESA prompt on your phone.</p>
        </div>
    </div>

    <script>
        function mpesaWait() {
            return {
                state: 'waiting',
                timer: null,
                async poll() {
                    try {
                        const response = await fetch('{{ route('checkout.status', $order) }}', {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await response.json();

                        if (data.paid && data.redirect) {
                            window.location = data.redirect;
                            return;
                        }

                        if (data.payment_status === 'failed' || data.payment_status === 'refund_required') {
                            this.state = 'failed';
                            clearTimeout(this.timer);
                            return;
                        }
                    } catch (error) {
                        // Network hiccup — keep polling.
                    }

                    this.timer = setTimeout(() => this.poll(), 5000);
                },
            };
        }
    </script>
</x-app-layout>
