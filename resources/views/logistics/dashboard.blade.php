<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Logistics') }}
            </h2>
            <span class="text-sm text-gray-500">
                Next collection day:
                <strong>{{ $nextCollectionDate->format('l, M j') }}</strong>
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="rounded-md bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Awaiting pickup</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['awaiting_pickup'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Paid orders not yet on a run</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Awaiting delivery</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['awaiting_delivery'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Collected, not yet delivered</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Delivered today</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['completed_today'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Completed orders</p>
                </div>
            </div>

            {{-- Build next route (admins only) --}}
            @if(auth()->user()->isAdmin())
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">Batch a collection run</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Groups all outstanding pickups and deliveries onto a Wednesday/Saturday route.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('logistics.routes.store') }}" class="flex items-end gap-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Collection date</label>
                                <select name="collection_date"
                                        class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($upcomingCollectionDates as $date)
                                        <option value="{{ $date->toDateString() }}">{{ $date->format('l, M j') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-primary-button>Build route</x-primary-button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Upcoming routes --}}
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Upcoming &amp; active runs</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($upcomingRoutes as $route)
                        <a href="{{ route('logistics.routes.show', $route) }}"
                           class="flex items-center justify-between px-5 py-4 hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $route->name ?? $route->collection_date->format('l, M j') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $route->collection_date->format('D, M j') }} ·
                                    {{ $route->stops_count }} stop(s) ·
                                    Driver: {{ $route->driver?->name ?? 'Unassigned' }}
                                </p>
                            </div>
                            <x-logistics.status-badge :status="$route->status" />
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">No upcoming runs. Batch one above.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent routes --}}
            @if($recentRoutes->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Recent runs</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach($recentRoutes as $route)
                            <a href="{{ route('logistics.routes.show', $route) }}"
                               class="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                                <span class="text-sm text-gray-700">
                                    {{ $route->collection_date->format('D, M j') }} · {{ $route->stops_count }} stop(s)
                                </span>
                                <x-logistics.status-badge :status="$route->status" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
