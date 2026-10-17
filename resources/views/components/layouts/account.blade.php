@props(['title' => null])

@php
    // Keep only the groups that still have at least one enabled item.
    $groups = collect(config('account.nav'))
        ->map(fn ($group) => [
            'label' => $group['label'],
            'items' => collect($group['items'])->where('enabled', true)->values()->all(),
        ])
        ->filter(fn ($group) => count($group['items']) > 0)
        ->values();

    // Matches the page itself and its sub-routes (e.g. account.profile.update).
    $isActive = fn (array $item) => request()->routeIs($item['route'], $item['route'] . '.*');
@endphp

<x-layouts.site :title="$title" :noindex="true">
    <div class="mx-auto max-w-6xl px-4 py-6 lg:flex lg:gap-6 lg:py-8">

        {{-- Mobile: sideways-scrolling tabs. --}}
        <nav aria-label="Menu da conta" class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden">
            @foreach ($groups->flatMap(fn ($group) => $group['items']) as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @if ($isActive($item)) aria-current="page" @endif
                    @class([
                        'inline-flex min-h-10 shrink-0 items-center whitespace-nowrap rounded-full px-4 text-sm font-medium transition',
                        'bg-primary text-on-primary' => $isActive($item),
                        'border border-line bg-surface text-ink hover:bg-canvas' => ! $isActive($item) && empty($item['danger']),
                        'border border-red-200 bg-surface text-red-600 hover:bg-red-50' => ! $isActive($item) && ! empty($item['danger']),
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Desktop: sidebar. --}}
        <aside class="hidden w-60 shrink-0 lg:block">
            <nav aria-label="Menu da conta" class="sticky top-6 space-y-5 rounded-card bg-surface p-3 shadow-card">
                @foreach ($groups as $group)
                    <div>
                        <p class="px-3 pb-1.5 text-xs font-semibold uppercase tracking-wide text-subtle">{{ $group['label'] }}</p>

                        @foreach ($group['items'] as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($isActive($item)) aria-current="page" @endif
                                @class([
                                    'flex min-h-11 items-center gap-3 rounded-control px-3 text-sm font-medium transition',
                                    'bg-primary-soft text-on-primary-soft' => $isActive($item),
                                    'text-ink hover:bg-canvas' => ! $isActive($item) && empty($item['danger']),
                                    'text-red-600 hover:bg-red-50' => ! $isActive($item) && ! empty($item['danger']),
                                ])
                            >
                                @svg('heroicon-o-' . $item['icon'], 'size-5')
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>
</x-layouts.site>