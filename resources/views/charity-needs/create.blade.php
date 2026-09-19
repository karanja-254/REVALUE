<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-forest">Publish a charity need</h2>
    </x-slot>

    <div class="mx-auto max-w-xl px-4 py-8">
        <form method="POST" action="{{ route('charity-needs.store') }}" class="rv-card space-y-5 p-6">
            @csrf
            <p class="text-sm text-forest/70">
                {{ $organization->name }} can tell donors exactly what is needed — mattresses, chairs, laptops — instead of waiting for random items.
            </p>
            <div>
                <label class="rv-label" for="category">Category</label>
                <select id="category" name="category" class="rv-input" required>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="rv-label" for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required class="rv-input">
            </div>
            <div>
                <label class="rv-label" for="description">Notes</label>
                <textarea id="description" name="description" rows="3" class="rv-input">{{ old('description') }}</textarea>
            </div>
            <button class="rv-btn-primary w-full">Publish need</button>
        </form>
    </div>
</x-app-layout>
