<x-layouts.site
    :title="$project->meta_title"
    :description="$project->meta_description"
    :image="$project->cover_url"
    type="article"
    :json-ld="$jsonLd">

    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <header class="mt-4 max-w-3xl">
            <a href="{{ $serviceUrl }}" class="inline-flex rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft hover:bg-primary-soft-hover">
                {{ $project->service->name }}
            </a>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $project->title }}</h1>
            <p class="mt-3 text-lg text-muted">{{ $project->summary }}</p>
        </header>

        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                <img
                    src="{{ $project->cover_url }}"
                    alt="{{ $project->title }}"
                    width="1200"
                    height="800"
                    fetchpriority="high"
                    class="w-full rounded-card bg-canvas object-cover shadow-card">

                @if ($body = \App\Support\RichContent::render($project->body))
                <article class="rich-content rounded-card bg-surface p-6 shadow-card sm:p-8">
                    {{ $body }}
                </article>
                @endif
            </div>

            {{-- Facts + call to action. Sticky on desktop so the CTA stays in view. --}}
            <aside class="h-fit space-y-4 lg:sticky lg:top-6">
                @php
                $facts = array_filter([
                    ['icon' => 'map-pin', 'label' => 'Local', 'value' => $project->location],
                    ['icon' => 'calendar', 'label' => 'Ano', 'value' => $project->year],
                    ['icon' => 'square-3-stack-3d', 'label' => 'Área', 'value' => $project->built_area ? number_format((float) $project->built_area, 2, ',', '.') . ' m²' : null],
                    ['icon' => 'user', 'label' => 'Cliente', 'value' => $project->client_name],
                ], fn ($fact) => filled($fact['value']));
                @endphp

                @if ($facts || $project->external_url)
                <div class="rounded-card bg-surface p-6 shadow-card">
                    <h2 class="text-lg font-bold text-ink">Sobre o projeto</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ($facts as $fact)
                        <div class="flex items-start gap-3">
                            @svg('heroicon-o-' . $fact['icon'], 'mt-0.5 size-5 shrink-0 text-primary')
                            <div>
                                <dt class="text-subtle">{{ $fact['label'] }}</dt>
                                <dd class="font-semibold text-ink">{{ $fact['value'] }}</dd>
                            </div>
                        </div>
                        @endforeach
                    </dl>

                    @if ($project->external_url)
                    <x-ui.button :href="$project->external_url" variant="outline" target="_blank" rel="noopener" class="mt-5 w-full">
                        @svg('heroicon-o-arrow-top-right-on-square', 'size-5')
                        Ver projeto no ar
                    </x-ui.button>
                    @endif
                </div>
                @endif

                <div class="rounded-card bg-surface p-6 shadow-card">
                    <h2 class="text-lg font-bold text-ink">Quer um projeto assim?</h2>
                    <p class="mt-1 text-sm text-muted">Conte o que você precisa e receba uma proposta sem compromisso.</p>
                    <x-site.showcase.cta-buttons :service="$project->service" :project="$project" stacked class="mt-5" />
                </div>
            </aside>
        </div>

        @if ($project->images->isNotEmpty())
        <section aria-labelledby="gallery-heading" class="mt-14">
            <h2 id="gallery-heading" class="text-2xl font-bold tracking-tight text-ink">Galeria</h2>

            <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($project->images as $image)
                <li>
                    <a href="#foto-{{ $loop->iteration }}" class="group block overflow-hidden rounded-card bg-canvas shadow-card focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <img
                            src="{{ $image->thumb_url }}"
                            alt="{{ $image->alt }}"
                            width="400"
                            height="300"
                            loading="lazy"
                            class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105">
                    </a>
                </li>
                @endforeach
            </ul>

            {{-- CSS-only lightbox (:target): no JavaScript, and each photo has its own shareable link. --}}
            @foreach ($project->images as $image)
            <div id="foto-{{ $loop->iteration }}" class="lightbox" role="dialog" aria-modal="true" aria-label="{{ $image->alt }}">
                <a href="#galeria-fechar" class="lightbox-backdrop" aria-label="Fechar"></a>

                <figure class="lightbox-figure">
                    <img src="{{ $image->url }}" alt="{{ $image->alt }}" loading="lazy" class="max-h-[80vh] w-auto max-w-full rounded-control">
                    <figcaption class="mt-3 text-center text-sm text-white/85">
                        {{ $image->caption ?: $image->alt }}
                        <span class="ml-2 text-white/60">{{ $loop->iteration }}/{{ $loop->count }}</span>
                    </figcaption>
                </figure>

                <a href="#galeria-fechar" class="lightbox-btn right-4 top-4" aria-label="Fechar">@svg('heroicon-o-x-mark', 'size-6')</a>

                @unless ($loop->first)
                <a href="#foto-{{ $loop->iteration - 1 }}" class="lightbox-btn left-4 top-1/2 -translate-y-1/2" aria-label="Foto anterior">@svg('heroicon-o-chevron-left', 'size-6')</a>
                @endunless

                @unless ($loop->last)
                <a href="#foto-{{ $loop->iteration + 1 }}" class="lightbox-btn right-4 top-1/2 -translate-y-1/2" aria-label="Próxima foto">@svg('heroicon-o-chevron-right', 'size-6')</a>
                @endunless
            </div>
            @endforeach
            <span id="galeria-fechar"></span>
        </section>
        @endif

        @if ($related->isNotEmpty())
        <section aria-labelledby="related-heading" class="mt-14">
            <h2 id="related-heading" class="text-2xl font-bold tracking-tight text-ink">Outros projetos de {{ mb_strtolower($project->service->name) }}</h2>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                <x-site.showcase.project-card :project="$item" />
                @endforeach
            </div>
        </section>
        @endif

        <x-site.showcase.cta-banner :service="$project->service" :project="$project" class="mt-14" />
    </div>
</x-layouts.site>
