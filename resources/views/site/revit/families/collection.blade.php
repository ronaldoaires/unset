<x-layouts.site
    :title="$seoTitle"
    :description="$intro"
    :canonical="$canonical"
    :noindex="$noindex"
    :json-ld="$jsonLd"
>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <div class="mt-4 flex items-start gap-4">
            @if ($logo)
                <img
                    src="{{ $logo }}"
                    alt="Logo {{ $heading }}"
                    width="64"
                    height="64"
                    class="size-16 shrink-0 rounded-control bg-surface object-contain p-1.5 shadow-card"
                >
            @endif

            <div>
                <h1 class="text-3xl font-bold leading-tight tracking-tight text-ink">{{ $heading }}</h1>
                <p class="mt-2 max-w-prose leading-relaxed text-muted">{{ $intro }}</p>
            </div>
        </div>

        {{-- Subcategory chips: internal links that scroll sideways on small screens. --}}
        @if (count($chips))
            <nav aria-label="Subcategorias" class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-1">
                @foreach ($chips as $chip)
                    <a
                        href="{{ $chip['url'] }}"
                        @if ($chip['active']) aria-current="page" @endif
                        @class([
                            'inline-flex min-h-10 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-sm font-medium transition',
                            'bg-primary text-on-primary' => $chip['active'],
                            'border border-line bg-surface text-ink hover:bg-canvas' => ! $chip['active'],
                        ])
                    >
                        {{ $chip['name'] }}
                    </a>
                @endforeach
            </nav>
        @endif

        <x-site.revit.family-listing
            :families="$families"
            :filters="$filters"
            :has-filters="$hasFilters"
            :action="$action"
            :categories="$categories"
            :brands="$brands"
        />
    </div>
</x-layouts.site>