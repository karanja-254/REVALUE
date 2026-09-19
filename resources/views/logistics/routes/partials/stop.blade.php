@php
    /** @var \App\Models\RouteStop $stop */
    $order = $stop->order;
    $isPickup = $stop->isPickup();

    // Literal class names so Tailwind's JIT compiler can see them.
    $accentBorder = $isPickup ? 'border-emerald-500' : 'border-blue-500';
    $accentText = $isPickup ? 'text-emerald-600' : 'text-blue-600';
    $accentBtnActive = $isPickup ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white';
@endphp

<div class="bg-white rounded-lg shadow-sm border-l-4 {{ $accentBorder }} p-5"
     x-data="{ verifyOpen: false, itemMatches: true }">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wide {{ $accentText }}">
                    {{ $isPickup ? 'Pickup' : 'Delivery' }} #{{ $stop->sequence }}
                </span>
                <x-logistics.status-badge :status="$stop->status" />
            </div>
            <p class="mt-1 font-medium text-gray-900">{{ $order->listing->title ?? 'Order #'.$order->id }}</p>
            <p class="text-sm text-gray-600">
                {{ $isPickup ? 'Seller' : 'Buyer' }}: {{ $stop->contact_name ?? '—' }}
            </p>
            <p class="text-sm text-gray-500">
                {{ $stop->address ?? 'Location not set' }}
                @if($stop->hasCoordinates())
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $stop->latitude }},{{ $stop->longitude }}"
                       target="_blank" rel="noopener"
                       class="text-indigo-600 hover:underline ml-1">Navigate</a>
                @endif
            </p>
            @if($stop->verification_result)
                <p class="mt-1 text-xs {{ $stop->verification_result === \App\Models\RouteStop::RESULT_MATCHED ? 'text-emerald-600' : 'text-red-600' }}">
                    Verification: {{ $stop->verification_result }}
                    @if($stop->verification_notes) — {{ $stop->verification_notes }} @endif
                </p>
            @endif
        </div>
        <a href="{{ route('tracking.show', $order) }}" class="text-xs text-indigo-600 hover:underline whitespace-nowrap">
            Order #{{ $order->id }}
        </a>
    </div>

    @if($canDrive && ! $stop->isFinished())
        <div class="mt-4 border-t border-gray-100 pt-4 space-y-3">
            {{-- In-transit status controls --}}
            <div class="flex flex-wrap gap-2">
                @foreach([\App\Models\RouteStop::STATUS_EN_ROUTE => 'En route', \App\Models\RouteStop::STATUS_ARRIVED => 'Arrived'] as $value => $label)
                    <form method="POST" action="{{ route('logistics.stops.status', $stop) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $value }}">
                        <button type="submit"
                                @class([
                                    'rounded-md px-3 py-1.5 text-xs font-medium',
                                    $accentBtnActive => $stop->status === $value,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200' => $stop->status !== $value,
                                ])>
                            {{ $label }}
                        </button>
                    </form>
                @endforeach

                <button type="button" @click="verifyOpen = !verifyOpen"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                    {{ $isPickup ? 'Verify pickup' : 'Verify delivery' }}
                </button>
            </div>

            {{-- Verification form --}}
            <div x-show="verifyOpen" x-cloak class="rounded-md bg-gray-50 p-4">
                @if($isPickup)
                    <form method="POST" action="{{ route('logistics.stops.verify-pickup', $stop) }}" class="space-y-3">
                        @csrf
                        <p class="text-sm font-medium text-gray-700">Does the physical item match the listing?</p>
                        <div class="flex gap-4 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="item_matches" value="1" checked @change="itemMatches = true">
                                Yes — item matches
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="item_matches" value="0" @change="itemMatches = false">
                                No — mismatch / damaged
                            </label>
                        </div>

                        <div x-show="itemMatches" class="space-y-1">
                            <label class="block text-xs font-medium text-gray-600">Seller's 4-digit pickup PIN</label>
                            <input type="text" name="pin" inputmode="numeric" maxlength="4" autocomplete="off"
                                   class="w-32 rounded-md border-gray-300 tracking-[0.4em] text-center font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="0000">
                            <p class="text-xs text-gray-500">Only ask for this once you've confirmed the item.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600">Notes (optional)</label>
                            <textarea name="notes" rows="2"
                                      class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Handover notes, or describe any mismatch…"></textarea>
                        </div>

                        <button type="submit"
                                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            <span x-show="itemMatches">Confirm pickup</span>
                            <span x-show="!itemMatches" x-cloak>Report failed pickup</span>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('logistics.stops.verify-delivery', $stop) }}" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-medium text-gray-600">Buyer's 4-digit delivery PIN</label>
                        <input type="text" name="pin" inputmode="numeric" maxlength="4" autocomplete="off"
                               class="w-32 rounded-md border-gray-300 tracking-[0.4em] text-center font-mono focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="0000">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Notes (optional)</label>
                            <textarea name="notes" rows="2"
                                      class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Any delivery notes…"></textarea>
                        </div>
                        <button type="submit"
                                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Confirm delivery
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
