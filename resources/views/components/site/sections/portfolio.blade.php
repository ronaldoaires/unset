@props(['projects'])

@if ($projects->isNotEmpty())
{{-- Home: proof that we deliver. Shows the projects marked as "Destaque". --}}
<section id="portfolio" class="border-t border-line bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-16">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold tracking-tight text-ink">Projetos que já entregamos</h2>
                <p class="mt-2 text-lg text-muted">Engenharia e tecnologia na prática. Clique para ver os detalhes e as fotos.</p>
            </div>

            <a href="{{ route('portfolio.index') }}" class="inline-flex min-h-12 items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                Ver portfólio completo
                @svg('heroicon-o-arrow-right', 'size-4')
            </a>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
            <x-site.showcase.project-card :project="$project" />
            @endforeach
        </div>
    </div>
</section>
@endif
