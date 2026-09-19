@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-clay pb-1 text-[15px] font-semibold text-forest'
            : 'inline-flex items-center text-[15px] font-medium text-forest/70 transition hover:text-forest';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
