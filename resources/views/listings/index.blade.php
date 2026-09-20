<x-public-layout :title="ucfirst($type).' listings'">
    <section class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="rv-section-title">
                <span class="rv-section-bar hidden md:block"></span>
                <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Browse</p>
                <h1 class="text-4xl font-bold tracking-tight text-forest">
                    @if ($type === 'donate') Donations looking for a home
                    @elseif ($type === 'recycle') Items for verified recyclers
                    @else Buy at the locked price
                    @endif
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-forest/70">
                    @if ($type === 'sell')
                        There is no bargaining chat. If the price is right, buy. If not, leave it.
                    @elseif ($type === 'donate')
                        Only verified charities can claim. Donors should not pay to give something away.
                    @else
                        Damaged goods and e-waste are routed away from landfills.
                    @endif
                </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('listings.index', ['type' => 'sell']) }}" class="{{ $type === 'sell' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Sell</a>
                <a href="{{ route('listings.index', ['type' => 'donate']) }}" class="{{ $type === 'donate' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Donate</a>
                <a href="{{ route('listings.index', ['type' => 'recycle']) }}" class="{{ $type === 'recycle' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Recycle</a>
                @if (Auth::user()?->canTrade() ?? true)
                    <a href="{{ route('listings.create', ['type' => $type]) }}" class="rv-btn-accent !py-2">List an item</a>
                @endif
            </div>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @empty
                <div class="rv-card p-8 text-forest/70 md:col-span-3">
                    Nothing here yet. List the first item for this path.
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $listings->links() }}
        </div>
    </section>
</x-public-layout>
