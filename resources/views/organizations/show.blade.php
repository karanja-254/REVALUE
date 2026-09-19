<x-public-layout :title="$organization->name">
    <section class="mx-auto max-w-3xl px-4 py-10">
        <x-flash />
        <div class="rv-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-clay">{{ $organization->type }}</p>
                    <h1 class="font-bold text-4xl text-forest">{{ $organization->name }}</h1>
                    <p class="mt-2 text-sm text-forest/70">{{ $organization->location }}</p>
                </div>
                <x-status-badge :status="$organization->verification_status" />
            </div>

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-forest/50">Contact</dt>
                    <dd class="font-medium text-forest">{{ $organization->contact_person }}</dd>
                </div>
                <div>
                    <dt class="text-forest/50">Phone</dt>
                    <dd class="font-medium text-forest">{{ $organization->phone }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-bold text-2xl text-forest">What they need</h2>
                @auth
                    @if (Auth::id() === $organization->user_id && $organization->isVerified() && $organization->isCharity())
                        <a href="{{ route('charity-needs.create') }}" class="rv-btn-ghost !py-2">Publish a need</a>
                    @endif
                @endauth
            </div>
            <div class="grid gap-3">
                @forelse ($organization->needs as $need)
                    <div class="rv-card flex items-center justify-between p-4">
                        <div>
                            <p class="font-semibold capitalize text-forest">{{ $need->quantity }} {{ $need->category }}</p>
                            <p class="text-sm text-forest/65">{{ $need->description }}</p>
                        </div>
                        <x-status-badge :status="$need->status" />
                    </div>
                @empty
                    <div class="rv-card p-6 text-sm text-forest/70">No published needs yet. Matching donations can still be claimed.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-public-layout>
