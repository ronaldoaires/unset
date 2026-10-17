@props(['family'])

{{-- The whole card is clickable through the stretched ".card-link" on the title.
     Hover effects follow that link only, so hovering the category/brand chips
     (which sit above it) does not trigger the card's own hover state. --}}
<article class="group/card relative flex flex-col overflow-hidden rounded-card bg-surface shadow-card transition has-[.card-link:hover]:-translate-y-0.5 has-[.card-link:hover]:shadow-lg">
    {{-- Fixed ratio box: the image is absolutely positioned so its natural
         size can never stretch the box (portrait and landscape look the same). --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-canvas">
        @if ($family->thumbnail_url)
        <img
            src="{{ $family->thumbnail_url }}"
            alt="{{ $family->name }} - família Revit"
            width="400"
            height="300"
            loading="lazy"
            class="absolute inset-0 size-full object-contain">
        @else
        <span class="absolute inset-0 flex items-center justify-center text-subtle">
            @svg('heroicon-o-cube', 'size-10')
        </span>
        @endif
        {{-- Above the stretched title link (z-10), so it never opens the family. --}}
        <x-site.revit.favorite-button :family="$family" class="absolute right-2 top-2 z-10" />
    </div>

    <div class="flex flex-1 flex-col gap-3 p-3 sm:p-4">
        <div>
            {{-- Two lines reserved so every card keeps the same rhythm. --}}
            <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-tight text-ink sm:min-h-[2.75rem] sm:text-base">
                <a
                    href="{{ route('revit.families.show', ['family' => $family->slug]) }}"
                    class="card-link after:absolute after:inset-0 focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-primary">
                    {{ $family->name }}
                </a>
            </h3>

            {{-- Category and brand chips sit above the stretched link (relative z-10),
                 so they navigate on their own. The soft border signals they are clickable.
                 Inactive ones have no public page and are not rendered as links. --}}
            @php
            $chipBase = 'inline-flex max-w-full items-center rounded-full border px-2.5 py-1 text-xs font-medium';
            $chipLink = $chipBase . ' relative z-10 border-line bg-canvas text-muted transition hover:border-primary hover:bg-primary-soft hover:text-on-primary-soft';
            $chipText = $chipBase . ' border-transparent text-subtle';
            @endphp

            <div class="mt-2 flex flex-wrap gap-1.5">
                @if ($family->category)
                @if ($family->category->is_active)
                <a
                    href="{{ route('revit.categories.show', ['category' => $family->category->slug]) }}"
                    class="{{ $chipLink }}"><span class="truncate">{{ $family->category->name }}</span></a>
                @else
                <span class="{{ $chipText }}"><span class="truncate">{{ $family->category->name }}</span></span>
                @endif
                @endif

                @if ($family->brand)
                @if ($family->brand->is_active)
                <a
                    href="{{ route('revit.brands.show', ['brand' => $family->brand->slug]) }}"
                    class="{{ $chipLink }}"><span class="truncate">{{ $family->brand->name }}</span></a>
                @else
                <span class="{{ $chipText }}"><span class="truncate">{{ $family->brand->name }}</span></span>
                @endif
                @endif
            </div>
        </div>

        {{-- Price block: fixed height so the button lines up across cards. --}}
        <div class="mt-auto flex min-h-11 flex-col justify-center">
            @if ($family->is_free)
            <span class="inline-flex w-fit items-center rounded-full bg-success-soft px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-success">
                Grátis
            </span>
            @elseif (($family->price ?? 0) > 0)
            <p class="text-xl font-bold leading-none text-ink">
                <span class="text-xs font-semibold text-subtle">R$</span>
                {{ number_format($family->price, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs font-medium text-primary">ou incluso no plano</p>
            @else
            <span class="inline-flex w-fit items-center rounded-full bg-primary-soft px-2.5 py-1 text-xs font-bold text-on-primary-soft">
                Incluso no plano
            </span>
            @endif
        </div>

        {{-- Visual button only: the stretched title link handles the click.
             Soft by default, filled only while the stretched link is hovered. --}}
        <span
            aria-hidden="true"
            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-control bg-primary-soft px-4 text-sm font-semibold text-on-primary-soft transition group-has-[.card-link:hover]/card:bg-primary group-has-[.card-link:hover]/card:text-on-primary">
            @svg('heroicon-o-arrow-down-tray', 'size-4')
            Baixar
        </span>
    </div>
</article>