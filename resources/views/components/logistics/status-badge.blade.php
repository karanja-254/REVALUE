@props(['status'])

@php
    $map = [
        // Route
        'planned' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-gray-100 text-gray-500',
        // Route stop
        'pending' => 'bg-gray-100 text-gray-700',
        'en_route' => 'bg-indigo-100 text-indigo-700',
        'arrived' => 'bg-amber-100 text-amber-700',
        'failed' => 'bg-red-100 text-red-700',
        'skipped' => 'bg-gray-100 text-gray-500',
        // Order
        'pending_payment' => 'bg-gray-100 text-gray-600',
        'paid' => 'bg-emerald-100 text-emerald-700',
        'scheduled' => 'bg-sky-100 text-sky-700',
        'picked_up' => 'bg-indigo-100 text-indigo-700',
        'out_for_delivery' => 'bg-amber-100 text-amber-700',
        'pickup_failed' => 'bg-red-100 text-red-700',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ str_replace('_', ' ', ucfirst($status)) }}
</span>
