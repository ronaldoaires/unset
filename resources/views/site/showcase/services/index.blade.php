<x-layouts.site
    title="Serviços de engenharia e tecnologia"
    description="Projetos arquitetônico, estrutural, elétrico e hidrossanitário com responsável técnico no CREA, e desenvolvimento de sites, sistemas e aplicativos. Atendimento em todo o Brasil."
    :json-ld="$jsonLd">

    <section class="bg-header text-on-header">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:py-20">
            <x-site.breadcrumb :items="$breadcrumbs" class="text-on-header/70 [&_[aria-current]]:text-on-header" />

            <h1 class="mt-6 max-w-3xl text-3xl font-bold tracking-tight sm:text-5xl">
                Engenharia e tecnologia para o seu projeto sair do papel
            </h1>
            <p class="mt-4 max-w-2xl text-lg text-on-header/85">
                Da planta ao aplicativo: projetos de engenharia com responsável técnico registrado e soluções digitais modernas, com atendimento online para todo o Brasil.
            </p>

            <x-site.showcase.cta-buttons inverse class="mt-8" />
        </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-16 px-4 py-14">
        @foreach ($areas as $area)
        @php($texts = config('showcase.areas.' . $area->value))
        <section aria-labelledby="area-{{ $area->value }}">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <h2 id="area-{{ $area->value }}" class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $texts['heading'] }}</h2>
                    <p class="mt-2 text-muted">{{ $texts['description'] }}</p>
                </div>

                <a href="{{ route('services.area', ['area' => $area->slug()]) }}" class="inline-flex min-h-12 items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                    Ver {{ mb_strtolower($area->getLabel()) }}
                    @svg('heroicon-o-arrow-right', 'size-4')
                </a>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($services[$area->value] as $service)
                <x-site.showcase.service-card :service="$service" />
                @endforeach
            </div>
        </section>
        @endforeach

        @if ($projects->isNotEmpty())
        <section aria-labelledby="portfolio-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="portfolio-heading" class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Projetos em destaque</h2>
                <a href="{{ route('portfolio.index') }}" class="inline-flex min-h-12 items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                    Ver portfólio
                    @svg('heroicon-o-arrow-right', 'size-4')
                </a>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                <x-site.showcase.project-card :project="$project" />
                @endforeach
            </div>
        </section>
        @endif

        <x-site.showcase.responsible />

        <x-site.showcase.cta-banner />
    </div>
</x-layouts.site>
