<x-public-layout :title="$listing->title">
    <section class="mx-auto max-w-6xl px-4 py-10">
        <x-flash />

        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="overflow-hidden rounded-2xl bg-forest-soft min-h-[280px]">
                <img src="{{ $listing->coverImage() }}" alt="{{ $listing->title }}" class="h-full min-h-[320px] w-full object-cover">
            </div>

            <div class="rv-card p-6">
                <div class="flex flex-wrap gap-2">
                    <x-status-badge :status="$listing->type" />
                    <x-status-badge :status="$listing->status" />
                </div>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-forest sm:text-4xl">{{ $listing->title }}</h1>
                <p class="mt-3 text-forest/70">{{ $listing->description }}</p>
                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-forest/50">Category</dt>
                        <dd class="capitalize font-medium text-forest">{{ $listing->category }}</dd>
                    </div>
                    <div>
                        <dt class="text-forest/50">Condition</dt>
                        <dd class="capitalize font-medium text-forest">{{ $listing->condition }}</dd>
                    </div>
                    <div>
                        <dt class="text-forest/50">Listed by</dt>
                        <dd class="font-medium text-forest">{{ $listing->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-forest/50">Collection</dt>
                        <dd class="font-medium text-forest">Wednesday / Saturday</dd>
                    </div>
                </dl>

                @if ($listing->isSell())
                    <div class="mt-6 rounded-2xl bg-sand p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Locked price</p>
                        <p class="mt-1 font-bold text-3xl text-forest">{{ $listing->displayPrice() ?? 'Awaiting ReValue offer' }}</p>
                        @if ($listing->displayPrice())
                            @php
                                $deliveryFee = (float) config('revalue.fees.delivery');
                                $serviceFee = (float) config('revalue.fees.service');
                                $itemPrice = (float) ($listing->final_price ?? $listing->suggested_price);
                            @endphp
                            <ul class="mt-3 space-y-1 text-sm text-forest/70">
                                <li class="flex justify-between"><span>Item</span><span>{{ $listing->displayPrice() }}</span></li>
                                <li class="flex justify-between"><span>ReValue delivery</span><span>KSh {{ number_format($deliveryFee) }}</span></li>
                                <li class="flex justify-between"><span>Platform service</span><span>KSh {{ number_format($serviceFee) }}</span></li>
                                <li class="flex justify-between border-t border-forest/10 pt-2 font-semibold text-forest">
                                    <span>Buyer total</span>
                                    <span>KSh {{ number_format($itemPrice + $deliveryFee + $serviceFee) }}</span>
                                </li>
                            </ul>
                            <p class="mt-3 text-xs text-forest/50">No surprise charges. Paystack verifies the payment on the server before ReValue marks this order paid.</p>
                        @else
                            <p class="mt-2 text-sm text-forest/60">The AI + pricing teammate will fill the suggested price. Until then this stays a draft.</p>
                        @endif
                    </div>
                    @auth
                        @if (Auth::id() === $listing->user_id && $listing->status === \App\Models\Listing::STATUS_DRAFT)
                            <div class="mt-4 rounded-2xl border border-forest/15 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Your ReValue offer</p>
                                <x-input-error :messages="$errors->get('price')" class="mt-2" />
                                @if ($listing->suggested_price)
                                    <p class="mt-1 text-sm text-forest/70">
                                        ReValue suggests <strong class="text-forest">KSh {{ number_format((float) $listing->suggested_price) }}</strong>.
                                        Accepting locks this price — no bargaining afterwards.
                                    </p>
                                    <form method="POST" action="{{ route('listings.accept-price', $listing) }}" class="mt-3">
                                        @csrf
                                        @method('PUT')
                                        <button class="rv-btn-primary w-full">Accept KSh {{ number_format((float) $listing->suggested_price) }} and publish</button>
                                    </form>
                                @else
                                    <p class="mt-1 text-sm text-forest/70">No automatic offer yet. Ask ReValue to price it by hand.</p>
                                @endif
                                @unless ($listing->manualReview)
                                    <form method="POST" action="{{ route('listings.request-review', $listing) }}" class="mt-2">
                                        @csrf
                                        @method('PUT')
                                        <button class="rv-btn-ghost w-full">Request manual review</button>
                                    </form>
                                @endunless
                            </div>
                        @endif

                        @if (Auth::user()->isSuperAdmin())
                            <div class="mt-4 rounded-2xl border border-clay/30 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Super admin · Override price</p>
                                <p class="mt-1 text-sm text-forest/70">
                                    Suggested {{ $listing->suggested_price ? 'KSh '.number_format((float) $listing->suggested_price) : '—' }}
                                    · Final {{ $listing->final_price ? 'KSh '.number_format((float) $listing->final_price) : '—' }}
                                </p>
                                <form method="POST" action="{{ route('admin.listings.override-price', $listing) }}" class="mt-3 space-y-3">
                                    @csrf
                                    <div>
                                        <label for="new_price" class="text-xs text-forest/60">New price (KSh)</label>
                                        <input id="new_price" name="new_price" type="number" step="1" min="1" required
                                            value="{{ old('new_price') }}"
                                            class="mt-1 w-full rounded-xl border-forest/20 text-forest focus:border-clay focus:ring-clay">
                                        <x-input-error :messages="$errors->get('new_price')" class="mt-1" />
                                    </div>
                                    <div>
                                        <label for="reason" class="text-xs text-forest/60">Reason</label>
                                        <input id="reason" name="reason" type="text" maxlength="500" required
                                            value="{{ old('reason', 'Hackathon demo price') }}"
                                            class="mt-1 w-full rounded-xl border-forest/20 text-forest focus:border-clay focus:ring-clay">
                                        <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                                    </div>
                                    <button class="rv-btn-primary w-full">Save override</button>
                                </form>
                            </div>
                        @endif
                    @endauth

                    @if ($listing->displayPrice() && $listing->isAvailable())
                        @auth
                            @if (Auth::id() !== $listing->user_id)
                                <form method="POST" action="{{ route('checkout.store', $listing) }}" class="mt-4">
                                    @csrf
                                    <x-input-error :messages="$errors->get('checkout')" class="mb-3" />
                                    <button class="rv-btn-primary w-full">Buy at this price</button>
                                </form>
                            @else
                                <p class="mt-4 text-sm text-forest/60">This is your listing. A buyer will pay this locked price.</p>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="rv-btn-primary mt-4 w-full">Log in to buy</a>
                        @endauth
                    @else
                        <a href="{{ route('listings.index') }}" class="rv-btn-ghost mt-4 w-full">Back to marketplace</a>
                    @endif
                @elseif ($listing->isDonate())
                    @if ($listing->donationClaim)
                        <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm text-sky-900">
                            Claimed by <strong>{{ $listing->donationClaim->organization->name }}</strong>.
                            Collection should be arranged so the donor pays nothing.
                        </div>
                    @else
                        <p class="mt-6 text-sm text-forest/70">Free to a verified charity. Unverified accounts cannot claim this item.</p>
                        @auth
                            @if (Auth::user()->organization?->isVerified() && Auth::user()->organization->isCharity() && $listing->isAvailable())
                                <form method="POST" action="{{ route('donations.claim', $listing) }}" class="mt-4">
                                    @csrf
                                    <button class="rv-btn-primary w-full">Claim this donation</button>
                                </form>
                            @elseif (Auth::id() !== $listing->user_id && Auth::user()->canApplyAsOrganization())
                                <a href="{{ route('organizations.create') }}" class="rv-btn-ghost mt-4 w-full">Apply as a verified charity to claim</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="rv-btn-primary mt-4 w-full">Log in to claim</a>
                        @endauth
                    @endif
                @else
                    <p class="mt-6 text-sm text-forest/70">This item is listed for a verified recycler. It is not for resale.</p>
                    <a href="{{ route('listings.index', ['type' => 'recycle']) }}" class="rv-btn-ghost mt-4 w-full">More recycle items</a>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
