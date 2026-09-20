<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-sand-dark bg-white/95 backdrop-blur-md">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 justify-between">
            <div class="flex items-center gap-10">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-lg font-bold text-forest">ReValue</span>
                </a>

                <div class="hidden items-center gap-8 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('listings.index')" :active="request()->routeIs('listings.index') && request('type', 'sell') === 'sell'">Marketplace</x-nav-link>
                    <x-nav-link :href="route('listings.index', ['type' => 'donate'])" :active="request('type') === 'donate'">Donate</x-nav-link>
                    <x-nav-link :href="route('organizations.index')" :active="request()->routeIs('organizations.*')">Charities</x-nav-link>
                    <x-nav-link :href="route('listings.mine')" :active="request()->routeIs('listings.mine')">My items</x-nav-link>
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">Orders</x-nav-link>
                    <x-nav-link :href="route('tracking.index')" :active="request()->routeIs('tracking.*')">My deliveries</x-nav-link>
                    @if (Auth::user()->isLogistics() || Auth::user()->isAdmin())
                        <x-nav-link :href="route('logistics.dashboard')" :active="request()->routeIs('logistics.*')">Logistics</x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.organizations.index')" :active="request()->routeIs('admin.organizations.*')">Verify charities</x-nav-link>
                        <x-nav-link :href="route('admin.manual-reviews.index')" :active="request()->routeIs('admin.manual-reviews.*')">Manual reviews</x-nav-link>
                        <x-nav-link :href="route('admin.payouts.index')" :active="request()->routeIs('admin.payouts.*')">Payouts</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden items-center sm:flex">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg border border-sand-dark bg-sand px-3 py-2 text-sm font-medium text-forest">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg p-2 text-forest">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('listings.index')">Marketplace</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('listings.index', ['type' => 'donate'])">Donate</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('organizations.index')">Charities</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('listings.mine')">My items</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('orders.index')">Orders</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tracking.index')" :active="request()->routeIs('tracking.*')">My deliveries</x-responsive-nav-link>
            @if (Auth::user()->isLogistics() || Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('logistics.dashboard')" :active="request()->routeIs('logistics.*')">Logistics</x-responsive-nav-link>
            @endif
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.organizations.index')">Verify charities</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.manual-reviews.index')">Manual reviews</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payouts.index')">Payouts</x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-sand-dark pb-3 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-forest">{{ Auth::user()->name }}</div>
                <div class="text-sm text-forest/55">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
