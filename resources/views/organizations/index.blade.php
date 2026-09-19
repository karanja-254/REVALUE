<x-public-layout title="Verified charities">
    <section class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Verified only</p>
                <h1 class="font-bold text-4xl text-forest">Charities that can claim donations</h1>
                <p class="mt-2 max-w-2xl text-sm text-forest/70">
                    Unverified organizations never appear here. That stops fake children's homes collecting free TVs and mattresses.
                </p>
            </div>
            <a href="{{ route('organizations.create') }}" class="rv-btn-primary">Apply for verification</a>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @forelse ($charities as $charity)
                <a href="{{ route('organizations.show', $charity) }}" class="rv-card block p-6">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-bold text-2xl text-forest">{{ $charity->name }}</h2>
                        <x-status-badge status="verified" />
                    </div>
                    <p class="mt-2 text-sm text-forest/65">{{ $charity->location }} · {{ $charity->contact_person }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse ($charity->needs as $need)
                            <span class="rounded-full bg-sand px-3 py-1 text-xs capitalize text-forest">{{ $need->quantity }} {{ $need->category }}</span>
                        @empty
                            <span class="text-xs text-forest/50">Open to matching donations</span>
                        @endforelse
                    </div>
                </a>
            @empty
                <div class="rv-card p-8 text-forest/70 md:col-span-2">No verified charities yet.</div>
            @endforelse
        </div>
    </section>
</x-public-layout>
