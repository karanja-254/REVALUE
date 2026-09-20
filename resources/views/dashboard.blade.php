<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Your ReValue desk</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-flash />

        @if (Auth::user()->canTrade())
            <div class="grid gap-3 sm:grid-cols-3">
                <a href="{{ route('listings.create', ['type' => 'sell']) }}" class="rv-btn-primary">Sell something</a>
                <a href="{{ route('listings.create', ['type' => 'donate']) }}" class="rv-btn-ghost">Donate something</a>
                <a href="{{ route('listings.create', ['type' => 'recycle']) }}" class="rv-btn-accent">Recycle something</a>
            </div>
        @else
            <div class="grid gap-3">
                <a href="{{ route('logistics.dashboard') }}" class="rv-btn-primary">Open logistics dashboard</a>
            </div>
        @endif

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rv-card p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-forest/50">Listings</p>
                <p class="font-bold text-4xl text-forest">{{ $listingCount }}</p>
            </div>
            <div class="rv-card p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-forest/50">Available now</p>
                <p class="font-bold text-4xl text-forest">{{ $availableCount }}</p>
            </div>
            <div class="rv-card p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-forest/50">Orders</p>
                <p class="font-bold text-4xl text-forest">{{ $orderCount }}</p>
            </div>
        </div>

        @if ($reviewableOrders->isNotEmpty())
            <section class="mt-10">
                <h3 class="font-bold text-xl text-forest">Rate completed handovers</h3>
                <div class="mt-4 grid gap-3">
                    @foreach ($reviewableOrders as $order)
                        <div class="rv-card flex items-center justify-between p-4">
                            <div>
                                <p class="font-semibold text-forest">{{ $order->listing->title }}</p>
                                <p class="text-sm text-forest/60">Completed · KSh {{ number_format((float) $order->total_amount) }}</p>
                            </div>
                            <a href="{{ route('reviews.create', $order) }}" class="rv-btn-primary !py-2">Rate</a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-10 grid gap-8 lg:grid-cols-2">
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-bold text-xl text-forest">Recent listings</h3>
                    <a href="{{ route('listings.mine') }}" class="text-sm font-semibold text-forest underline">All</a>
                </div>
                <div class="space-y-3">
                    @forelse ($listings as $listing)
                        <a href="{{ route('listings.show', $listing) }}" class="rv-card flex items-center justify-between p-4">
                            <div>
                                <p class="font-semibold text-forest">{{ $listing->title }}</p>
                                <p class="text-xs capitalize text-forest/55">{{ $listing->type }} · {{ $listing->status }}</p>
                            </div>
                            <x-status-badge :status="$listing->status" />
                        </a>
                    @empty
                        <p class="text-sm text-forest/60">No listings yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="mb-4 font-bold text-xl text-forest">Charity status</h3>
                @if ($organization)
                    <div class="rv-card p-5">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-2xl text-forest">{{ $organization->name }}</p>
                            <x-status-badge :status="$organization->verification_status" />
                        </div>
                        <a href="{{ route('organizations.create') }}" class="mt-4 inline-block text-sm font-semibold text-forest underline">View application</a>
                    </div>
                @elseif (Auth::user()->canApplyAsOrganization())
                    <div class="rv-card p-5 text-sm text-forest/70">
                        Selling does not need extra KYC. Charities and recyclers must apply.
                        <a href="{{ route('organizations.create') }}" class="mt-4 rv-btn-ghost !py-2">Apply for verification</a>
                    </div>
                @else
                    <div class="rv-card p-5 text-sm text-forest/70">
                        Staff accounts do not apply for charity verification.
                    </div>
                @endif

                <h3 class="mb-4 mt-8 font-bold text-xl text-forest">Recent orders</h3>
                <div class="space-y-3">
                    @forelse ($orders as $order)
                        <a href="{{ route('orders.show', $order) }}" class="rv-card block p-4">
                            <p class="font-semibold text-forest">{{ $order->listing->title }}</p>
                            <p class="text-xs capitalize text-forest/55">{{ str_replace('_', ' ', $order->order_status) }} · {{ $order->payment_status }} · KSh {{ number_format((float) $order->total_amount) }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-forest/60">No purchases yet. Buy a locked-price item to start Paystack checkout.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
