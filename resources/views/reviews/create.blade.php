<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-forest">Rate this handover</h2>
    </x-slot>

    <div class="mx-auto max-w-xl px-4 py-8">
        <form method="POST" action="{{ route('reviews.store', $order) }}" class="rv-card space-y-5 p-6">
            @csrf
            <p class="text-sm text-forest/70">
                {{ $order->listing->title }} is completed. Rate the ReValue experience — not a bargaining chat.
            </p>

            @foreach ([
                'accurate_description' => 'Accurate description',
                'smooth_delivery' => 'Smooth delivery',
                'professional_handling' => 'Professional handling',
                'punctual_pickup' => 'Punctual pickup',
            ] as $name => $label)
                <div>
                    <label class="rv-label" for="{{ $name }}">{{ $label }}</label>
                    <select id="{{ $name }}" name="{{ $name }}" class="rv-input" required>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(old($name, 5) == $i)>{{ $i }} / 5</option>
                        @endfor
                    </select>
                    <x-input-error :messages="$errors->get($name)" class="mt-2" />
                </div>
            @endforeach

            <div>
                <label class="rv-label" for="comment">Comment</label>
                <textarea id="comment" name="comment" rows="3" class="rv-input">{{ old('comment') }}</textarea>
            </div>

            <button class="rv-btn-primary w-full">Submit rating</button>
        </form>
    </div>
</x-app-layout>
