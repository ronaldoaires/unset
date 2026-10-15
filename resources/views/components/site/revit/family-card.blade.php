@props(['family'])

{{-- The whole card is clickable through the stretched link on the title. --}}
<article class="group relative flex flex-col overflow-hidden rounded-card bg-surface shadow-card transition hover:-translate-y-0.5">
    <div class="relative aspect-[4/3] bg-canvas">
        @if ($family->thumbnail_url)
            <img
                src="{{ $family->thumbnail_url }}"
                alt="{{ $family->name }} - família Revit"
                width="400"
                height="300"
                loading="lazy"
                class="size-full object-cover"
            >
        @else
            <span class="flex size-full items-center justify-center text-subtle">
                @svg('heroicon-o-cube', 'size-10')
            </span>
        @endif

        @if ($family->is_featured)
            <span class="absolute left-3 top-3 rounded-full bg-accent px-3 py-1 text-xs font-semibold text-ink">
                Destaque
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 p-4">
        <div>
            <h3 class="text-base font-semibold leading-tight text-ink">
                <a
                    href="{{ route('revit.families.show', ['family' => $family->slug]) }}"
                    class="after:absolute after:inset-0 focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-primary"
                >
                    {{ $family->name }}
                </a>
            </h3>

            <p class="mt-1 text-sm text-subtle">
                {{ $family->category?->name }}@if ($family->brand) · {{ $family->brand->name }}@endif
            </p>
        </div>

        <div class="mt-auto flex items-center justify-between gap-2">
            @if ($family->is_free)
                <span class="text-lg font-bold text-ink">Grátis</span>
            @elseif (($family->price ?? 0) > 0)
                <span class="text-lg font-bold text-ink">R$ {{ number_format($family->price, 2, ',', '.') }}</span>
            @else
                <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft">Assinantes</span>
            @endif

            <span class="text-xs font-medium text-subtle">Revit {{ $family->revit_version }}</span>
        </div>
    </div>
</article>