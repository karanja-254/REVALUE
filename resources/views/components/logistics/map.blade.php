@props([
    'data' => ['stops' => [], 'driver' => null, 'center' => null],
    'mapsKey' => null,
    'locationsUrl' => null,
    'pollMs' => 12000,
    'height' => 'h-80 sm:h-96',
])

@php
    $stops = $data['stops'] ?? [];
    $driver = $data['driver'] ?? null;
    $colors = ['pickup' => 'text-emerald-600', 'delivery' => 'text-blue-600'];
@endphp

<div
    data-logistics-map
    @if($mapsKey) data-maps-key="{{ $mapsKey }}" @endif
    @if($locationsUrl) data-locations-url="{{ $locationsUrl }}" @endif
    data-poll-ms="{{ $pollMs }}"
    class="w-full"
>
    {{-- Data payload consumed by resources/js/logistics/map.js --}}
    <script type="application/json" data-map-payload>{!! json_encode($data) !!}</script>

    {{-- Live map canvas (revealed by JS when a Maps key is present) --}}
    <div data-map-canvas class="hidden w-full {{ $height }} rounded-lg overflow-hidden border border-gray-200"></div>

    {{-- Graceful fallback: works with no API key and on every browser --}}
    <div data-map-fallback class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
        @unless($mapsKey)
            <p class="text-xs text-amber-700 mb-3">
                Live map disabled (no <code>GOOGLE_MAPS_API_KEY</code> set). Showing locations as a list.
            </p>
        @endunless

        @if($driver && ($driver['lat'] ?? null) !== null)
            <div class="mb-3 flex items-center gap-2 text-sm">
                <span class="inline-block h-2.5 w-2.5 rounded-full {{ ($driver['stale'] ?? false) ? 'bg-gray-400' : 'bg-amber-500' }}"></span>
                <span class="font-medium text-gray-700">Driver</span>
                <span class="text-gray-500">
                    {{ number_format($driver['lat'], 5) }}, {{ number_format($driver['lng'], 5) }}
                    @if($driver['stale'] ?? false) (last known) @else (live) @endif
                </span>
            </div>
        @endif

        @if(count($stops))
            <ol class="space-y-2">
                @foreach($stops as $stop)
                    <li class="flex items-start gap-3 text-sm">
                        <span class="mt-0.5 font-semibold {{ $colors[$stop['type']] ?? 'text-gray-600' }}">
                            {{ ucfirst($stop['type']) }}{{ isset($stop['sequence']) ? ' #'.$stop['sequence'] : '' }}
                        </span>
                        <span class="text-gray-600">
                            {{ $stop['address'] ?? 'Location not set' }}
                            @if(($stop['lat'] ?? null) !== null)
                                <span class="text-gray-400">
                                    ({{ number_format($stop['lat'], 5) }}, {{ number_format($stop['lng'], 5) }})
                                </span>
                            @endif
                            @if($stop['status'] ?? null)
                                <span class="text-gray-400">· {{ str_replace('_', ' ', $stop['status']) }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="text-sm text-gray-500">No mappable locations yet.</p>
        @endif
    </div>
</div>
