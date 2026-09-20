<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-forest">My items</h2>
            @if (Auth::user()->canTrade())
                <a href="{{ route('listings.create') }}" class="rv-btn-primary !py-2">New listing</a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-flash />
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($listings as $listing)
                <x-listing-card :listing="$listing" />
            @empty
                <div class="rv-card p-8 text-forest/70 md:col-span-3">
                    You have not listed anything yet.
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    </div>
</x-app-layout>
