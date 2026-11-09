<x-layouts.site
    title="Plataforma de engenharia BIM"
    description="Famílias Revit, templates, planilhas e ferramentas de cálculo para projetos de arquitetura e engenharia."
    :json-ld="$jsonLd">
    {{-- Hero as a white bordered box on the gray canvas, so it stands apart
         from the plans section below, which is white edge to edge. --}}
    <section class="bg-canvas">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
            <div class="rounded-card border border-line bg-surface px-6 py-12 text-center shadow-card sm:px-10 sm:py-16">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Plataforma de engenharia BIM para seus projetos
                </h1>
                <p class="mt-4 text-lg text-muted">
                    Famílias Revit, templates, planilhas e ferramentas de cálculo em um só lugar.
                </p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <x-ui.button :href="route('revit.families.index')" size="lg">Explorar famílias Revit</x-ui.button>
                    @if ($areas->isNotEmpty())
                    <x-ui.button href="#servicos" variant="tonal" size="lg">Contratar um projeto</x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Section order: change it here; each section hides itself when empty. --}}
    <x-site.sections.textures :textures="$textures" />

    <x-site.sections.plans :plans="$plans" />

    <x-site.sections.services :areas="$areas" />

    <x-site.sections.portfolio :projects="$projects" />

</x-layouts.site>