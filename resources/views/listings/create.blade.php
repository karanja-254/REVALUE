<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">
            @if ($type === 'donate') Donate something
            @elseif ($type === 'recycle') Recycle something
            @else Sell something
            @endif
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-flash />

        <div class="mb-6 flex gap-2">
            <a href="{{ route('listings.create', ['type' => 'sell']) }}" class="{{ $type === 'sell' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Sell</a>
            <a href="{{ route('listings.create', ['type' => 'donate']) }}" class="{{ $type === 'donate' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Donate</a>
            <a href="{{ route('listings.create', ['type' => 'recycle']) }}" class="{{ $type === 'recycle' ? 'rv-btn-primary' : 'rv-btn-ghost' }} !py-2">Recycle</a>
        </div>

        <form method="POST" action="{{ route('listings.store') }}" enctype="multipart/form-data" class="rv-card space-y-5 p-6">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <p class="text-sm text-forest/70">
                @if ($type === 'sell')
                    Do not type your own asking price. ReValue will suggest a fixed offer. After you accept, buyers cannot bargain.
                @elseif ($type === 'donate')
                    Verified charities that need this category can claim it. You should not pay for the pickup.
                @else
                    Use this when the item is too damaged to resell. Verified recyclers handle collection.
                @endif
            </p>

            <div>
                <label class="rv-label" for="title">Item title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="rv-input" placeholder="Samsung 43 inch Smart TV">
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <label class="rv-label" for="description">Details</label>
                <textarea id="description" name="description" rows="4" class="rv-input" placeholder="Condition, brand, what is included">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="rv-label" for="category">Category</label>
                    <select id="category" name="category" class="rv-input" required>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>
                <div>
                    <label class="rv-label" for="condition">Condition</label>
                    <select id="condition" name="condition" class="rv-input" required>
                        @foreach ($conditions as $value => $label)
                            <option value="{{ $value }}" @selected(old('condition') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('condition')" class="mt-2" />
                </div>
            </div>

            <div>
                <label class="rv-label" for="image">Photo</label>
                <input id="image" name="image" type="file" accept="image/*" class="rv-input bg-white">
                <p class="mt-1 text-xs text-forest/50">JPG, PNG or WebP. Max 5MB. AI classification is owned by the pricing team.</p>
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>

            <button class="rv-btn-primary w-full">
                @if ($type === 'donate') Publish donation
                @elseif ($type === 'recycle') List for recycling
                @else Save listing
                @endif
            </button>
        </form>
    </div>
</x-app-layout>
