<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-forest">Charity verification</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-10 px-4 py-8">
        <x-flash />

        @foreach (['Pending' => $pending, 'Verified' => $verified, 'Rejected' => $rejected] as $label => $organizations)
            <section>
                <h3 class="mb-4 font-bold text-xl text-forest">{{ $label }}</h3>
                <div class="grid gap-4">
                    @forelse ($organizations as $organization)
                        <div class="rv-card p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-2xl text-forest">{{ $organization->name }}</h4>
                                        <x-status-badge :status="$organization->type" />
                                        <x-status-badge :status="$organization->verification_status" />
                                    </div>
                                    <p class="mt-1 text-sm text-forest/70">
                                        {{ $organization->contact_person }} · {{ $organization->email }} · {{ $organization->phone }} · {{ $organization->location }}
                                    </p>
                                    @if ($organization->registration_details)
                                        <p class="mt-2 text-sm text-forest/60">{{ $organization->registration_details }}</p>
                                    @endif
                                    @if ($organization->review_notes)
                                        <p class="mt-2 text-xs text-forest/50">Notes: {{ $organization->review_notes }}</p>
                                    @endif
                                </div>

                                @if ($organization->isPending())
                                    <div class="flex min-w-[240px] flex-col gap-2">
                                        <form method="POST" action="{{ route('admin.organizations.verify', $organization) }}">
                                            @csrf
                                            <button class="rv-btn-primary w-full !py-2">Verify</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.organizations.reject', $organization) }}" class="space-y-2">
                                            @csrf
                                            <input name="review_notes" required class="rv-input" placeholder="Rejection reason">
                                            <button class="rv-btn-ghost w-full !py-2">Reject</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-forest/15 p-6 text-sm text-forest/60">None.</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>
