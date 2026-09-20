<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Manual pricing reviews</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-4 px-4 py-8">
        <x-flash />
        <p class="text-sm text-forest/65">Items ReValue could not price automatically. Set the locked price here — the seller cannot bargain afterwards.</p>

        @forelse ($reviews as $review)
            <div class="rv-card p-5">
                <div class="flex flex-col gap-5 md:flex-row md:justify-between">
                    <div class="flex gap-4">
                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-forest-soft">
                            <img src="{{ $review->listing->coverImage() }}" alt="{{ $review->listing->title }}" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-forest">{{ $review->listing->title }}</h3>
                            <p class="text-sm text-forest/65">
                                {{ $review->listing->user->name }} ·
                                <span class="capitalize">{{ $review->listing->category ?? 'uncategorised' }}</span> ·
                                <span class="capitalize">{{ $review->listing->condition ?? 'condition unknown' }}</span>
                            </p>
                            <p class="mt-1 text-sm text-forest/65">
                                Suggested {{ $review->listing->suggested_price ? 'KSh '.number_format((float) $review->listing->suggested_price) : '—' }}
                                · Final {{ $review->listing->final_price ? 'KSh '.number_format((float) $review->listing->final_price) : '—' }}
                            </p>
                            @if ($review->notes)
                                <p class="mt-2 rounded-xl bg-sand px-3 py-2 text-xs text-forest/70">{{ $review->notes }}</p>
                            @endif
                            <p class="mt-2 text-xs text-forest/50">
                                Queued {{ $review->created_at->diffForHumans() }}
                                @if ($review->assignedAdmin)
                                    · assigned to {{ $review->assignedAdmin->name }}
                                @endif
                            </p>
                            <a href="{{ route('listings.show', $review->listing) }}" class="mt-2 inline-block text-sm font-semibold text-forest underline">View listing</a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.manual-reviews.approve', $review) }}" class="min-w-[260px] space-y-2">
                        @csrf
                        @method('PUT')
                        <input name="final_price" type="number" step="1" min="1" required class="rv-input" placeholder="Final price (KSh)" value="{{ old('final_price') }}">
                        <x-input-error :messages="$errors->get('final_price')" />
                        <input name="notes" type="text" maxlength="500" class="rv-input" placeholder="Admin notes" value="{{ old('notes') }}">
                        <x-input-error :messages="$errors->get('notes')" />
                        <button class="rv-btn-primary w-full !py-2">Approve &amp; Publish</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rv-card p-8 text-sm text-forest/70">Nothing waiting for manual pricing.</div>
        @endforelse

        <div>{{ $reviews->links() }}</div>
    </div>
</x-app-layout>
