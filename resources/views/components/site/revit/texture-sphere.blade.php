@props([
    'texture',
    'size' => 'card', // card | large
])

{{-- The texture as a lit sphere: the tile repeated on a circle, a soft highlight on the
     top left and a shadow on the edge give a 3D "material ball" look without any 3D code.
     Gradients are inline styles so the effect does not depend on rebuilt CSS. --}}
@php
    $image = $size === 'large' ? $texture->preview_url : $texture->thumbnail_url;
    $tile  = $size === 'large' ? '70%' : '85%';
@endphp

<div {{ $attributes->merge(['class' => 'relative aspect-square rounded-full']) }}
     style="box-shadow: 0 18px 30px -12px rgba(15, 27, 45, .45);">
    <div class="absolute inset-0 overflow-hidden rounded-full"
         style="background-color: {{ $texture->shading_color ?? $texture->base_color ?? '#9ca3af' }};
                @if ($image) background-image: url('{{ $image }}'); background-size: {{ $tile }}; background-repeat: repeat; background-position: center; @endif">
    </div>
    {{-- Light from the top left, darker towards the bottom right edge. --}}
    <div class="pointer-events-none absolute inset-0 rounded-full"
         style="background:
            radial-gradient(circle at 32% 28%, rgba(255,255,255,.55) 0%, rgba(255,255,255,.18) 18%, rgba(255,255,255,0) 38%),
            radial-gradient(circle at 50% 50%, rgba(0,0,0,0) 52%, rgba(0,0,0,.28) 78%, rgba(0,0,0,.6) 100%);">
    </div>
</div>
