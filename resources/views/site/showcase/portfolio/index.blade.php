<x-layouts.site
    :title="$title"
    description="Portfólio de projetos de engenharia (arquitetônico, estrutural, elétrico e hidrossanitário) e de tecnologia (sites, sistemas e aplicativos) entregues pela UnSet."
    :canonical="$canonical"
    :noindex="$noindex"
    :json-ld="$jsonLd">

    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <div class="mt-4 max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-ink">Portfólio</h1>
            <p class="mt-2 text-muted">Alguns dos projetos que já entregamos. Clique para ver os detalhes e as fotos.</p>
        </div>

        {{-- Filters are plain links (GET), so any filtered list can be shared. --}}
        @if (count($filters) > 0)
        @php
        $chip = 'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium transition';
        $on = $chip . ' border-primary bg-primary text-on-primary';
        $off = $chip . ' border-line bg-surface text-muted hover:border-primary hover:text-primary';
        @endphp

        <nav aria-label="Filtrar projetos" class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('portfolio.index') }}" class="{{ ! $area && ! $service ? $on : $off }}">Todos</a>

            @foreach ($filters as $areaSlug => $group)
            <a href="{{ route('portfolio.index', ['area' => $areaSlug]) }}" class="{{ $area?->slug() === $areaSlug && ! $service ? $on : $off }}">{{ $group['label'] }}</a>
            @endforeach
        </nav>

        @php($current = $service ? $service->area->slug() : $area?->slug())
        @if ($current && isset($filters[$current]) && $filters[$current]['services']->count() > 1)
        <nav aria-label="Filtrar por serviço" class="mt-3 flex flex-wrap gap-2">
            @foreach ($filters[$current]['services'] as $option)
            <a href="{{ route('portfolio.index', ['servico' => $option->slug]) }}" class="{{ $service?->id === $option->id ? $on : $off }}">{{ $option->name }}</a>
            @endforeach
        </nav>
        @endif
        @endif

        @if ($projects->isEmpty())
        <x-ui.empty-state
            class="mt-8"
            icon="photo"
            title="Nenhum projeto por aqui ainda"
            description="Estamos organizando o portfólio. Enquanto isso, conheça os serviços.">
            <x-ui.button :href="route('services.index')">Ver serviços</x-ui.button>
        </x-ui.empty-state>
        @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
            <x-site.showcase.project-card :project="$project" heading="h2" />
            @endforeach
        </div>

        {{ $projects->links('components.site.pagination') }}
        @endif

        <x-site.showcase.cta-banner class="mt-14" />
    </div>
</x-layouts.site>
