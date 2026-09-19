@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-sand-dark focus:border-forest-mid focus:ring-forest-mid rounded-2xl shadow-sm bg-sand/50']) }}>
