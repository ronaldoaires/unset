@php($items = collect(config('site.nav'))->where('enabled', true))

<header class="bg-header text-on-header">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4">
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">
            {{ config('app.name') }}
        </a>

        {{-- Desktop navigation. On mobile the bottom bar takes over. --}}
        <nav class="hidden flex-1 items-center gap-1 md:flex" aria-label="Principal">
            @foreach ($items as $item)
                @if (($item['submenu'] ?? null) === 'services' && ($menuAreas ??= \App\Support\ShowcaseMenu::areas()))
                    <x-site.services-menu :label="$item['label']" :areas="$menuAreas" :active="request()->routeIs($item['active'] ?? $item['route'])" />
                    @continue
                @endif

                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'rounded-control px-3 py-2 text-sm font-medium transition hover:bg-on-header/10',
                        'bg-on-header/15' => request()->routeIs($item['active'] ?? $item['route']),
                    ])
                    @if (request()->routeIs($item['active'] ?? $item['route'])) aria-current="page" @endif
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            {{-- Plugin download: full button on desktop, icon on small screens. --}}
            <a
                href="{{ route('plugin.download') }}"
                class="inline-flex min-h-12 items-center gap-2 rounded-control border border-on-header/30 px-3 text-sm font-semibold transition hover:bg-on-header/10"
                title="Plugin UnSet BIM para Revit 2025 ou superior (Windows)"
                aria-label="Baixar o plugin para Revit"
            >
                @svg('heroicon-o-arrow-down-tray', 'size-5')
                <span class="hidden md:inline">Baixar plugin</span>
            </a>

            <x-site.user-menu />
        </div>
    </div>
</header>