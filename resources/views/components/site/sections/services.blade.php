@props(['areas'])

@if ($areas->isNotEmpty())
{{-- Home: the two service areas side by side, each with its own list. --}}
<section id="servicos" class="border-t border-line bg-canvas">
    <div class="mx-auto max-w-6xl px-4 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-ink">Precisa de um projeto? A gente faz.</h2>
            <p class="mt-3 text-lg text-muted">
                Projetos de engenharia com responsável técnico registrado e soluções digitais sob medida. {{ config('showcase.company.service_mode') }}.
            </p>
        </div>

        <div @class(['mt-10 grid gap-6', 'lg:grid-cols-2' => $areas->count() > 1])>
            @foreach ($areas as $area => $services)
            @php
            $enum = \App\Enums\ServiceArea::from($area);
            $texts = config('showcase.areas.' . $area);
            $profile = config('showcase.profiles.' . $area);
            @endphp

            <article class="flex flex-col rounded-card bg-surface p-6 shadow-card sm:p-8">
                <div class="flex items-start gap-4">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-primary text-on-primary">
                        @svg('heroicon-o-' . $texts['icon'], 'size-6')
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-ink">
                            <a href="{{ route('services.area', ['area' => $enum->slug()]) }}" class="hover:text-primary">{{ $texts['heading'] }}</a>
                        </h3>
                        <p class="mt-1 text-sm text-muted">{{ $texts['headline'] }}</p>
                    </div>
                </div>

                <ul class="mt-6 grid gap-2 sm:grid-cols-2">
                    @foreach ($services as $service)
                    <li>
                        <a
                            href="{{ route('services.show', ['area' => $enum->slug(), 'service' => $service->slug]) }}"
                            class="flex min-h-12 items-center gap-3 rounded-control border border-line px-3 text-sm font-semibold text-ink transition hover:border-primary hover:bg-primary-soft hover:text-on-primary-soft">
                            @svg('heroicon-o-' . $service->icon_name, 'size-5 shrink-0 text-primary')
                            {{ $service->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>

                <p class="mt-6 flex items-center gap-2 text-sm text-subtle">
                    @svg('heroicon-o-shield-check', 'size-5 text-primary')
                    {{ config('showcase.person.name') }} · {{ $profile['title'] }} · {{ $profile['badge'] }}
                </p>

                <div class="mt-6 flex flex-col gap-3 pt-2 sm:mt-auto sm:flex-row">
                    <x-ui.button :href="route('quotes.create')" class="sm:flex-1">Solicitar orçamento</x-ui.button>
                    <x-ui.button :href="route('services.area', ['area' => $enum->slug()])" variant="tonal" class="sm:flex-1">
                        Ver {{ mb_strtolower($enum->getLabel()) }}
                    </x-ui.button>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif
