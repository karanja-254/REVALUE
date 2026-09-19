<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'ReValue') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen bg-sand px-4 py-10">
            <div class="mx-auto flex max-w-md flex-col items-center">
                <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2">
                    <x-application-logo class="h-12 w-12" />
                    <span class="text-2xl font-bold text-forest">ReValue</span>
                </a>
                <p class="mb-6 text-center text-sm text-forest/60">Sell, donate or recycle without bargaining.</p>
                <div class="w-full rounded-2xl border border-sand-dark bg-white p-6 shadow-card">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
