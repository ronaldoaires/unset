@props(['tone' => 'neutral'])

@php
    // Full class names on purpose: Tailwind only generates classes it can read in the source.
    $tones = [
        'success' => 'bg-success-soft text-success',
        'info'    => 'bg-primary-soft text-on-primary-soft',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger'  => 'bg-red-100 text-red-700',
        'neutral' => 'bg-canvas text-muted',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>