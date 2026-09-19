@props(['status'])

@php
    $styles = [
        'draft' => 'bg-sand-dark text-forest',
        'under_review' => 'bg-amber-100 text-amber-900',
        'available' => 'bg-forest-soft text-forest',
        'sold' => 'bg-slate-200 text-slate-700',
        'donated' => 'bg-sky-100 text-sky-900',
        'recycled' => 'bg-stone-200 text-stone-700',
        'cancelled' => 'bg-rose-100 text-rose-800',
        'pending' => 'bg-amber-100 text-amber-900',
        'verified' => 'bg-forest-soft text-forest',
        'rejected' => 'bg-rose-100 text-rose-800',
        'claimed' => 'bg-sky-100 text-sky-900',
        'completed' => 'bg-forest-soft text-forest',
        'paid' => 'bg-forest-soft text-forest',
        'pending_payment' => 'bg-amber-100 text-amber-900',
        'refund_required' => 'bg-rose-100 text-rose-900',
        'refunded' => 'bg-slate-200 text-slate-700',
        'ready' => 'bg-amber-100 text-amber-900',
        'sell' => 'bg-clay text-white',
        'donate' => 'bg-forest text-white',
        'recycle' => 'bg-[#0a1128] text-white',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider '.($styles[$status] ?? 'bg-sand-dark text-forest')]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
