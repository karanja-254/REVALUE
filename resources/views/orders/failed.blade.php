<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-forest">Payment not verified</h2>
    </x-slot>

    <div class="mx-auto max-w-xl px-4 py-8">
        <div class="rv-card p-6">
            <p class="text-forest/80">{{ session('status', 'Paystack did not confirm this payment. ReValue will not mark the order paid from the browser alone.') }}</p>
            <a href="{{ route('listings.index') }}" class="rv-btn-primary mt-6">Back to marketplace</a>
        </div>
    </div>
</x-app-layout>
