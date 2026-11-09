@props(['texture'])

@php
    $sizeText = $texture->tile_width_mm % 10 === 0 && $texture->tile_height_mm % 10 === 0
        ? ($texture->tile_width_mm / 10) . ' × ' . ($texture->tile_height_mm / 10) . ' cm'
        : $texture->tile_width_mm . ' × ' . $texture->tile_height_mm . ' mm';
@endphp

{{-- Whole card clickable through the stretched link on the title, like the family card. --}}
<article class="group/card relative flex flex-col items-center rounded-card bg-surface p-4 shadow-card transition has-[.card-link:hover]:-translate-y-0.5 has-[.card-link:hover]:shadow-lg sm:p-5">
    @unless ($texture->is_free)
        <x-ui.premium-badge class="absolute left-3 top-3" />
    @endunless

    <x-site.revit.texture-sphere :texture="$texture" class="mt-2 w-3/4 transition duration-300 group-has-[.card-link:hover]/card:scale-105" />

    <h3 class="mt-5 line-clamp-2 min-h-[2.5rem] text-center text-sm font-semibold leading-tight text-ink sm:text-base">
        <a
            href="{{ route('revit.textures.show', ['texture' => $texture->slug]) }}"
            class="card-link after:absolute after:inset-0 after:rounded-card focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-primary">
            {{ $texture->name }}
        </a>
    </h3>

    <p class="mt-1 text-center text-xs text-subtle">
        {{ $texture->category?->name }} · {{ $sizeText }}
    </p>

    <div class="mt-3">
        @if ($texture->is_free)
            <span class="inline-flex items-center rounded-full bg-success-soft px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-success">Grátis</span>
        @else
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600">
                <x-icons.crown class="size-4" />
                Assinantes
            </span>
        @endif
    </div>
</article>
