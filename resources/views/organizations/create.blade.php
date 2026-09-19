<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-forest">Charity or recycler verification</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-flash />

        @if ($organization)
            <div class="rv-card p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-2xl text-forest">{{ $organization->name }}</h3>
                        <p class="text-sm text-forest/65">{{ ucfirst($organization->type) }} · {{ $organization->location }}</p>
                    </div>
                    <x-status-badge :status="$organization->verification_status" />
                </div>
                @if ($organization->isPending())
                    <p class="mt-4 text-sm text-forest/70">Your application is with ReValue admin. You cannot claim donations until it is verified.</p>
                @elseif ($organization->isVerified())
                    <p class="mt-4 text-sm text-forest/70">This organization can appear to donors and claim matching items.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('listings.index', ['type' => 'donate']) }}" class="rv-btn-primary !py-2">Claim donations</a>
                        @if ($organization->isCharity())
                            <a href="{{ route('charity-needs.create') }}" class="rv-btn-ghost !py-2">Publish a need</a>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-sm text-rose-800">Rejected: {{ $organization->review_notes }}</p>
                @endif
            </div>
        @else
            <form method="POST" action="{{ route('organizations.store') }}" enctype="multipart/form-data" class="rv-card space-y-5 p-6">
                @csrf
                <p class="text-sm text-forest/70">Ordinary sellers do not need this. Only charities and recyclers apply so fake organizations cannot collect free goods.</p>

                <div>
                    <label class="rv-label" for="type">Organization type</label>
                    <select id="type" name="type" class="rv-input" required>
                        <option value="charity" @selected(old('type') === 'charity')>Charity / children's home / NGO</option>
                        <option value="recycler" @selected(old('type') === 'recycler')>Recycler / waste business</option>
                    </select>
                </div>

                <div>
                    <label class="rv-label" for="name">Organization name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="rv-input" placeholder="Hope Children's Home">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="rv-label" for="contact_person">Contact person</label>
                        <input id="contact_person" name="contact_person" value="{{ old('contact_person', Auth::user()->name) }}" required class="rv-input">
                    </div>
                    <div>
                        <label class="rv-label" for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required class="rv-input" placeholder="07xxxxxxxx">
                    </div>
                </div>

                <div>
                    <label class="rv-label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', Auth::user()->email) }}" required class="rv-input">
                </div>

                <div>
                    <label class="rv-label" for="location">Physical location</label>
                    <input id="location" name="location" value="{{ old('location') }}" required class="rv-input" placeholder="Parklands, Nairobi">
                </div>

                <div>
                    <label class="rv-label" for="registration_details">Registration details</label>
                    <textarea id="registration_details" name="registration_details" rows="3" class="rv-input">{{ old('registration_details') }}</textarea>
                </div>

                <div>
                    <label class="rv-label" for="website">Website or social page</label>
                    <input id="website" name="website" type="url" value="{{ old('website') }}" class="rv-input" placeholder="https://">
                    <x-input-error :messages="$errors->get('website')" class="mt-2" />
                </div>

                <div>
                    <label class="rv-label" for="supporting_document">Supporting document</label>
                    <input id="supporting_document" name="supporting_document" type="file" class="rv-input bg-white">
                    <x-input-error :messages="$errors->get('supporting_document')" class="mt-2" />
                </div>

                <button class="rv-btn-primary w-full">Submit application</button>
            </form>
        @endif
    </div>
</x-app-layout>
