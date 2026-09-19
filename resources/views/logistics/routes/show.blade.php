@php
    $user = auth()->user();
    $isAssignedDriver = $user->id === $route->driver_id;
    $canDrive = $isAssignedDriver || $user->isAdmin();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('logistics.dashboard') }}" class="text-sm text-indigo-600 hover:underline">&larr; Logistics</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $route->name ?? $route->collection_date->format('l, M j') }}
                </h2>
            </div>
            <x-logistics.status-badge :status="$route->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="rounded-md bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Overview --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-gray-600 space-y-1">
                        <p>Collection date: <strong>{{ $route->collection_date->format('l, M j, Y') }}</strong></p>
                        <p>Driver: <strong>{{ $route->driver?->name ?? 'Unassigned' }}</strong></p>
                        <p>Stops: <strong>{{ $route->stops->count() }}</strong>
                           ({{ $route->pickups->count() }} pickup, {{ $route->deliveries->count() }} delivery)</p>
                    </div>
                    @if($canDrive)
                        <div class="flex gap-2">
                            @if($route->status === \App\Models\LogisticsRoute::STATUS_PLANNED)
                                <form method="POST" action="{{ route('logistics.routes.start', $route) }}">
                                    @csrf
                                    <x-primary-button>Start route</x-primary-button>
                                </form>
                            @elseif($route->status === \App\Models\LogisticsRoute::STATUS_IN_PROGRESS)
                                <form method="POST" action="{{ route('logistics.routes.complete', $route) }}">
                                    @csrf
                                    <x-secondary-button>Complete route</x-secondary-button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Progress bar --}}
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Progress</span>
                        <span>{{ $route->progressPercent() }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full bg-emerald-500" style="width: {{ $route->progressPercent() }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Driver location sharing (assigned driver only) --}}
            @if($isAssignedDriver)
                <div class="bg-white rounded-lg shadow-sm p-5" data-driver-tracker
                     data-endpoint="{{ route('logistics.driver.location') }}" data-interval-ms="10000">
                    <h3 class="font-medium text-gray-900">Live location</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Share your GPS so buyers and sellers can see you approaching. Works from your phone browser.
                    </p>
                    <div class="mt-3 flex items-center gap-4">
                        <button type="button" data-tracker-toggle
                                class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                            Start sharing my location
                        </button>
                        <span data-tracker-status class="text-sm text-gray-500"></span>
                    </div>
                </div>
            @endif

            {{-- Map --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-medium text-gray-900 mb-3">Route map</h3>
                <x-logistics.map
                    :data="$mapData"
                    :maps-key="$mapsKey"
                    :locations-url="route('logistics.routes.locations', $route)"
                    height="h-96" />
            </div>

            {{-- Stops --}}
            <div class="space-y-4">
                @foreach($route->stops as $stop)
                    @include('logistics.routes.partials.stop', ['stop' => $stop, 'canDrive' => $canDrive])
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
