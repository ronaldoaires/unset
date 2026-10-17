@props([
    'tone' => 'warning', // warning | danger | info
    'icon' => 'exclamation-triangle',
])

@php
    // Full class names on purpose: Tailwind only generates classes it can read in the source.
    $tones = [
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger'  => 'border-red-200 bg-red-50 text-red-800',
        'info'    => 'border-brand-200 bg-primary-soft text-on-primary-soft',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-control border p-3 text-sm font-medium leading-snug ' . ($tones[$tone] ?? $tones['warning'])]) }}>
    @svg('heroicon-o-' . $icon, 'mt-0.5 size-5 shrink-0')
    <div>{{ $slot }}</div>
</div>