<x-layouts.site
    title="Plataforma de engenharia BIM"
    description="Famílias Revit, templates, planilhas e ferramentas de cálculo para projetos de arquitetura e engenharia."
    :json-ld="$jsonLd">
    <section class="mx-auto max-w-6xl px-4 py-16 text-center">
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
            Plataforma de engenharia BIM para seus projetos
        </h1>
        <p class="mt-4 text-lg text-muted">
            Famílias Revit, templates, planilhas e ferramentas de cálculo em um só lugar.
        </p>

        <x-ui.button :href="route('revit.families.index')" size="lg" class="mt-8">Explorar famílias Revit</x-ui.button>
    </section>

    <x-site.sections.plans :plans="$plans" />
    
</x-layouts.site>