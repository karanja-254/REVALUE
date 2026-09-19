@props(['listing'])

<a href="{{ route('listings.show', $listing) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-sand-dark bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-forest/5">
    <div class="relative aspect-[4/3] overflow-hidden bg-forest-soft">
        <img src="{{ $listing->coverImage() }}" alt="{{ $listing->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        <div class="absolute inset-0 bg-gradient-to-t from-forest/55 via-transparent to-transparent"></div>
        <div class="absolute left-3 top-3 flex flex-wrap gap-2">
            <x-status-badge :status="$listing->type" />
        </div>
        <span class="absolute bottom-3 right-3 inline-flex items-center gap-1 rounded-full bg-clay px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-red ring-2 ring-white/90 transition group-hover:gap-2">
            View
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
        @if ($listing->isSell() && $listing->displayPrice())
            <span class="absolute bottom-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-forest">{{ $listing->displayPrice() }}</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-clay">{{ $listing->category }}</p>
        <h3 class="mt-1 line-clamp-2 text-lg font-bold leading-snug text-forest group-hover:text-forest-mid">{{ $listing->title }}</h3>
        <p class="mt-2 line-clamp-2 text-sm text-forest/55">{{ $listing->description ?: 'What you see is the listing. No surprise price at the door.' }}</p>
        <div class="mt-auto flex items-end justify-between border-t border-sand-dark/70 pt-4">
            <div>
                @if ($listing->isSell())
                    <p class="text-xl font-bold text-forest">{{ $listing->displayPrice() ?? 'Price pending' }}</p>
                    <p class="text-xs text-forest/50">Fixed. No bargaining.</p>
                @elseif ($listing->isDonate())
                    <p class="text-sm font-semibold text-forest">Free to a verified charity</p>
                    <p class="text-xs text-forest/50">Donor does not pay transport</p>
                @else
                    <p class="text-sm font-semibold text-forest">For recycling</p>
                    <p class="text-xs text-forest/50">Not for resale</p>
                @endif
            </div>
            <x-status-badge :status="$listing->status" />
        </div>
    </div>
</a>
