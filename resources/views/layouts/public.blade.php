<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'ReValue' }} · Sell. Donate. Recycle.</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-sand font-sans text-ink" x-data="{ open: false, scrolled: false }" x-init="scrolled = window.scrollY > 48; window.addEventListener('scroll', () => scrolled = window.scrollY > 48, { passive: true })">
        <header
            class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
            :class="scrolled || {{ $hero ? 'false' : 'true' }}
                ? 'border-b border-sand-dark bg-white/95 shadow-[0_4px_24px_rgba(10,17,40,0.06)] backdrop-blur-md'
                : 'border-b border-white/10 bg-[#0a1128]/20 backdrop-blur-xl'"
        >
            <div class="mx-auto hidden h-20 max-w-7xl items-center px-6 lg:flex lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="h-11 w-11" />
                    <span class="text-xl font-bold tracking-tight" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">ReValue</span>
                </a>

                <nav class="ml-12 flex items-center gap-9 text-[15px] font-medium">
                    <a href="{{ route('listings.index') }}" class="transition" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest/70 hover:text-forest' : 'text-white/80 hover:text-white'">Marketplace</a>
                    <a href="{{ route('listings.index', ['type' => 'donate']) }}" class="transition" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest/70 hover:text-forest' : 'text-white/80 hover:text-white'">Donate</a>
                    <a href="{{ route('listings.index', ['type' => 'recycle']) }}" class="transition" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest/70 hover:text-forest' : 'text-white/80 hover:text-white'">Recycle</a>
                    <a href="{{ route('organizations.index') }}" class="transition" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest/70 hover:text-forest' : 'text-white/80 hover:text-white'">Charities</a>
                </nav>

                <div class="ml-auto flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rv-btn-ghost !py-2">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-[15px] font-medium transition" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest/80 hover:text-forest' : 'text-white/85 hover:text-white'">Log in</a>
                        <a href="{{ route('register') }}" class="rv-btn-primary !py-2">Join ReValue</a>
                    @endauth
                </div>
            </div>

            <div class="flex h-[72px] items-center justify-between px-4 lg:hidden">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-10 w-10" />
                    <span class="font-bold" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">ReValue</span>
                </a>
                <button @click="open = ! open" type="button" class="flex h-11 w-11 items-center justify-center rounded-lg" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>

            <div x-show="open" x-cloak class="border-t px-4 py-4 lg:hidden" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'border-sand-dark bg-white' : 'border-white/10 bg-[#0a1128]/95'">
                <div class="grid gap-1 text-sm font-medium">
                    <a href="{{ route('listings.index') }}" class="py-2.5" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">Marketplace</a>
                    <a href="{{ route('listings.index', ['type' => 'donate']) }}" class="py-2.5" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">Donate</a>
                    <a href="{{ route('listings.index', ['type' => 'recycle']) }}" class="py-2.5" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">Recycle</a>
                    <a href="{{ route('organizations.index') }}" class="py-2.5" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">Charities</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="py-2.5 text-clay">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="py-2.5" :class="scrolled || {{ $hero ? 'false' : 'true' }} ? 'text-forest' : 'text-white'">Log in</a>
                        <a href="{{ route('register') }}" class="rv-btn-primary mt-2">Join ReValue</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="{{ $hero ? '' : 'pt-20' }}">
            {{ $slot }}
        </main>

        <footer class="border-t border-sand-dark bg-white px-4 py-10 text-sm text-forest/65">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="font-semibold text-forest">ReValue · Give things another life.</p>
                <p>No bargaining. Fixed prices. Verified handover. Nairobi collection on Wednesday and Saturday.</p>
            </div>
        </footer>
    </body>
</html>
