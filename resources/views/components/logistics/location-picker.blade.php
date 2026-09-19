@props([
    'action',
    'type' => 'pickup', // pickup | delivery
    'address' => '',
    'latitude' => null,
    'longitude' => null,
    'notes' => '',
    'mapsKey' => null,
    'defaultLat' => -1.286389,
    'defaultLng' => 36.817223,
])

@php
    $isPickup = $type === 'pickup';
    $title = $isPickup ? 'Share your pickup location' : 'Share your delivery location';
    $description = $isPickup
        ? 'Pin where the ReValue driver should collect the item from you. Works from your phone browser.'
        : 'Pin where the ReValue driver should deliver your item. Works from your phone browser.';
    $markerColor = $isPickup ? '#059669' : '#2563eb';
    $buttonClass = $isPickup ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700';
@endphp

<div
    data-location-picker
    @if($mapsKey) data-maps-key="{{ $mapsKey }}" @endif
    data-marker-color="{{ $markerColor }}"
    data-default-lat="{{ $defaultLat }}"
    data-default-lng="{{ $defaultLng }}"
    {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white p-5']) }}
>
    <h3 class="font-medium text-gray-900">{{ $title }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>

    @if(session('location_status'))
        <p class="mt-3 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-800">
            {{ session('location_status') }}
        </p>
    @endif

    <form method="POST" action="{{ $action }}" class="mt-4 space-y-4">
        @csrf

        {{-- Map canvas (shown when Google Maps key is set) --}}
        <div data-picker-canvas class="h-64 w-full rounded-lg border border-gray-200 overflow-hidden {{ $mapsKey ? '' : 'hidden' }}"></div>

        {{-- Fallback when no Maps key --}}
        <div data-no-map-hint class="{{ $mapsKey ? 'hidden' : '' }} rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
            <p>No map API key configured. Tap <strong>Use my current location</strong> below, or enter an address — your GPS coordinates will be saved for the driver.</p>
            <p class="mt-2 font-mono text-xs text-gray-500">Coordinates: <span data-coords-display>{{ $latitude && $longitude ? number_format($latitude, 5).', '.number_format($longitude, 5) : 'Not set yet' }}</span></p>
        </div>

        @if($mapsKey)
            <p class="text-xs text-gray-500">
                Tap the map to drop a pin, drag it to adjust, or use your current location.
                <span class="font-mono">(<span data-coords-display>{{ $latitude && $longitude ? number_format($latitude, 5).', '.number_format($longitude, 5) : 'Not set yet' }}</span>)</span>
            </p>
        @endif

        <div class="flex flex-wrap gap-2">
            <button type="button" data-use-current
                    class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                Use my current location
            </button>
        </div>

        <p data-picker-status class="text-sm text-gray-500"></p>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Address or landmark</label>
            <input type="text" name="address" value="{{ old('address', $address) }}" required
                   placeholder="{{ $isPickup ? 'e.g. Westlands, Nairobi — Gate B' : 'e.g. Kilimani, Nairobi — Apartment 4B' }}"
                   class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <x-input-error :messages="$errors->get('address')" class="mt-1" />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Notes for the driver (optional)</label>
            <textarea name="notes" rows="2"
                      placeholder="{{ $isPickup ? 'Call when you arrive, item is in the garage…' : 'Leave with watchman if not home…' }}"
                      class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $notes) }}</textarea>
        </div>

        <input type="hidden" name="latitude" data-lat value="{{ old('latitude', $latitude) }}">
        <input type="hidden" name="longitude" data-lng value="{{ old('longitude', $longitude) }}">
        <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
        <x-input-error :messages="$errors->get('longitude')" class="mt-1" />

        <button type="submit" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white {{ $buttonClass }}">
            Save {{ $isPickup ? 'pickup' : 'delivery' }} location
        </button>
    </form>
</div>
