@php($items = collect(config('site.nav'))->where('enabled', true))

<header class="bg-header text-on-header">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4">
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">
            {{ config('app.name') }}
        </a>

        {{-- Desktop navigation. On mobile the bottom bar takes over. --}}
        <nav class="hidden flex-1 items-center gap-1 md:flex" aria-label="Principal">
            @foreach ($items as $item)
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

        <x-site.user-menu />
    </div>
</header>