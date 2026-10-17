@props([
'variant' => 'primary', // primary | tonal | outline | ghost | inverse | danger
'size' => 'md', // md | lg
])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-control font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60';

$variants = [
'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
'tonal' => 'bg-primary-soft text-on-primary-soft hover:bg-primary-soft-hover',
'outline' => 'border border-line bg-surface text-ink hover:bg-canvas',
'ghost' => 'text-primary hover:bg-primary-soft',
// For use on top of the header color.
'inverse' => 'bg-on-header text-header hover:bg-on-header/90',
'danger' => 'bg-red-600 text-white hover:bg-red-700',
];

// 48px minimum tap target on touch screens.
$sizes = [
'md' => 'min-h-12 px-5 text-sm',
'lg' => 'min-h-14 px-6 text-base',
];

$classes = "{$base} {$variants[$variant]} {$sizes[$size]}";
@endphp

@if ($attributes->has('href'))
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif