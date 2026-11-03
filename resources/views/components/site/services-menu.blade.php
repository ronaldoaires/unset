@props(['label', 'areas', 'active' => false])

{{-- Desktop "Serviços" submenu. <details> opens it without JavaScript;
     app.js only closes it on an outside click or Esc. --}}
<details data-dropdown class="group/menu relative">
    <summary
        @class([
            'flex cursor-pointer list-none items-center gap-1 rounded-control px-3 py-2 text-sm font-medium transition hover:bg-on-header/10 [&::-webkit-details-marker]:hidden',
            'bg-on-header/15' => $active,
        ])
        @if ($active) aria-current="page" @endif>
        {{ $label }}
        @svg('heroicon-o-chevron-down', 'size-4 transition group-open/menu:rotate-180')
    </summary>

    <div class="absolute left-0 z-30 mt-2 w-[min(40rem,calc(100vw-2rem))] rounded-card bg-surface p-6 text-ink shadow-lg ring-1 ring-line">
        <div @class(["grid gap-6", "sm:grid-cols-2" => count($areas) > 1])>
            @foreach ($areas as $area)
            <div>
                <a href="{{ $area['url'] }}" class="text-xs font-bold uppercase tracking-wide text-primary hover:underline">{{ $area['heading'] }}</a>

                <ul class="mt-3 space-y-1">
                    @foreach ($area['services'] as $service)
                    <li>
                        <a href="{{ $service['url'] }}" class="flex min-h-10 items-center gap-2 rounded-control px-2 text-sm font-medium text-ink hover:bg-canvas hover:text-primary">
                            @svg('heroicon-o-chevron-right', 'size-4 text-subtle')
                            {{ $service['name'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4 text-sm">
            <a href="{{ route('services.index') }}" class="font-semibold text-primary hover:underline">Ver todos os serviços</a>
            <x-ui.button :href="route('quotes.create')" class="min-h-10! px-4">Solicitar orçamento</x-ui.button>
        </div>
    </div>
</details>
