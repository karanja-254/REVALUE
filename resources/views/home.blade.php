<x-public-layout title="Give Things Another Life" hero>
    <section class="relative min-h-[560px] overflow-hidden lg:min-h-[640px]">
        <img
            src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=2000&q=70"
            alt=""
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-gradient-to-br from-[#0a1128]/92 via-[#0a1128]/72 to-[#ef3d32]/45"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a1128] via-[#0a1128]/40 to-transparent"></div>
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-[#ef3d32]/20 blur-3xl"></div>

        <div class="relative mx-auto flex min-h-[560px] max-w-6xl flex-col items-center justify-center px-4 pb-28 pt-28 text-center lg:min-h-[640px] lg:pt-32">
            <span class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-medium uppercase tracking-wider text-white/90 backdrop-blur-sm">
                <span class="h-2 w-2 rounded-full bg-clay"></span>
                Kenya · circular marketplace
            </span>
            <h1 class="max-w-3xl text-[2rem] font-bold leading-[1.15] tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">
                Give Things Another <span class="text-clay">Life.</span>
            </h1>
            <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
                Sell, donate or recycle unwanted items without bargaining, transport headaches or unreliable strangers.
            </p>
            @if (Auth::user()?->canTrade() ?? true)
                <div class="mt-8 grid w-full max-w-xl gap-3 sm:grid-cols-3">
                    <a href="{{ route('listings.create', ['type' => 'sell']) }}" class="rv-btn-primary">Sell something</a>
                    <a href="{{ route('listings.create', ['type' => 'donate']) }}" class="rv-btn border border-white/20 bg-white/10 text-white backdrop-blur hover:bg-white/20">Donate something</a>
                    <a href="{{ route('listings.create', ['type' => 'recycle']) }}" class="rv-btn-accent">Recycle something</a>
                </div>
            @else
                <div class="mt-8">
                    <a href="{{ route('logistics.dashboard') }}" class="rv-btn-primary">Open logistics dashboard</a>
                </div>
            @endif
            <p class="mt-5 text-sm text-white/70">No bargaining. The price you accept is the price.</p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-6 text-sm text-white/75 sm:gap-10">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-white">{{ $availableCount > 0 ? $availableCount : '—' }}</span>
                    <span>Items ready</span>
                </div>
                <div class="hidden h-4 w-px bg-white/20 sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-[#4ade80]">Paystack</span>
                    <span>Ready</span>
                </div>
                <div class="hidden h-4 w-px bg-white/20 sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-white">Wed / Sat</span>
                    <span>Collection</span>
                </div>
                <div class="hidden h-4 w-px bg-white/20 sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-white">{{ $charityCount > 0 ? $charityCount : '—' }}</span>
                    <span>Verified charities</span>
                </div>
            </div>
        </div>
    </section>

    <section class="relative z-10 -mt-16 px-4 pb-8 sm:-mt-20">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-sand-dark/70">
            <div class="h-1 bg-clay"></div>
            <div class="grid gap-0 md:grid-cols-5">
                @foreach ([
                    ['1', 'Upload', 'Photos and Sell, Donate or Recycle.'],
                    ['2', 'Get an offer', 'Rules set the price. AI only classifies.'],
                    ['3', 'Accept', 'It locks. No bargaining chat.'],
                    ['4', 'We collect', 'Wednesday or Saturday pickup.'],
                    ['5', 'Get paid', 'Paystack in. Payout after PIN.'],
                ] as $step)
                    <div class="border-t border-sand-dark p-5 md:border-l md:border-t-0 md:first:border-l-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-clay">{{ $step[0] }}</p>
                        <p class="mt-1 font-semibold text-forest">{{ $step[1] }}</p>
                        <p class="mt-1 text-sm text-forest/60">{{ $step[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-forest py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-clay">Why ReValue</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Selling in Nairobi should not feel like a gamble</h2>
            </div>
            <div class="mt-16 grid gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-clay">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1"/></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold">Sell</h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/70">Fixed ReValue price. Buyer either buys or leaves. Pickup PIN, then payout ledger.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-clay">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold">Donate</h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/70">Verified charities claim what they actually need. The donor does not pay transport.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-clay">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold">Recycle</h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/70">Broken TVs, scrap and e-waste go to verified recyclers instead of a dumpsite.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div class="rv-section-title">
                <span class="rv-section-bar"></span>
                <div>
                    <h2 class="text-2xl font-semibold text-forest">Fixed-price items ready now</h2>
                    <p class="text-sm text-forest/55">No make-offer button. Buy or leave.</p>
                </div>
            </div>
            <a href="{{ route('listings.index') }}" class="text-sm font-semibold text-clay">See all →</a>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            @forelse ($sellListings as $listing)
                <x-listing-card :listing="$listing" />
            @empty
                <div class="rv-card p-6 text-sm text-forest/70 md:col-span-3">
                    No sell listings yet. Be the first to upload something.
                </div>
            @endforelse
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
            <div class="mb-8 flex items-end justify-between gap-4">
                <div class="rv-section-title">
                    <span class="rv-section-bar"></span>
                    <div>
                        <h2 class="text-2xl font-semibold text-forest">Charities that can claim donations</h2>
                        <p class="text-sm text-forest/55">Unverified organizations never appear here.</p>
                    </div>
                </div>
                <a href="{{ route('organizations.index') }}" class="text-sm font-semibold text-clay">All charities →</a>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                @forelse ($charities as $charity)
                    <a href="{{ route('organizations.show', $charity) }}" class="rv-card block p-6 transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-forest">{{ $charity->name }}</h3>
                            <x-status-badge status="verified" />
                        </div>
                        <p class="mt-2 text-sm text-forest/60">{{ $charity->location }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($charity->needs as $need)
                                <span class="rounded-full bg-sand px-3 py-1 text-xs capitalize text-forest">{{ $need->quantity }} {{ $need->category }}</span>
                            @empty
                                <span class="text-xs text-forest/50">Open to matching donations</span>
                            @endforelse
                        </div>
                    </a>
                @empty
                    <div class="rv-card p-6 text-sm text-forest/70 md:col-span-3">
                        No verified charities yet. Organizations can apply after login.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-public-layout>
