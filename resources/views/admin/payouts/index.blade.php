<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Seller payout ledger</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-4 px-4 py-8">
        <x-flash />
        <p class="text-sm text-forest/65">Paystack collects buyer money. Sellers are paid later from the ReValue M-Pesa ledger after pickup verification.</p>

        @forelse ($payouts as $payout)
            <div class="rv-card p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-forest">{{ $payout->order->listing->title }}</h3>
                        <p class="text-sm text-forest/65">
                            {{ $payout->seller->name }} · KSh {{ number_format((float) $payout->amount) }} ·
                            <span class="capitalize">{{ $payout->status }}</span>
                        </p>
                        @if ($payout->mpesa_receipt)
                            <p class="mt-1 text-xs text-forest/50">{{ $payout->mpesa_number }} · {{ $payout->mpesa_receipt }}</p>
                        @endif
                    </div>
                    @if ($payout->status !== 'paid')
                        <form method="POST" action="{{ route('admin.payouts.pay', $payout) }}" class="min-w-[260px] space-y-2">
                            @csrf
                            <input name="mpesa_number" required class="rv-input" placeholder="M-Pesa number">
                            <input name="mpesa_receipt" required class="rv-input" placeholder="M-Pesa receipt">
                            <button class="rv-btn-primary w-full !py-2">Record payout</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rv-card p-8 text-sm text-forest/70">No payout rows yet. They appear after a verified Paystack payment.</div>
        @endforelse

        <div>{{ $payouts->links() }}</div>
    </div>
</x-app-layout>
