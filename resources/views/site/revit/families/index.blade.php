<x-layouts.site
    title="Famílias Revit para download"
    description="Biblioteca de famílias Revit (.rfa) gratuitas e premium: portas, janelas, mobiliário e muito mais, prontas para usar nos seus projetos BIM."
    :canonical="$canonical"
    :noindex="$hasFilters"
    :json-ld="$jsonLd"
>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <h1 class="mt-4 text-3xl font-bold leading-tight tracking-tight text-ink">Famílias Revit</h1>
        <p class="mt-2 max-w-prose leading-relaxed text-muted">
            Biblioteca de famílias Revit (.rfa) gratuitas e premium, prontas para usar nos seus projetos.
        </p>

        <x-site.revit.family-listing
            :families="$families"
            :filters="$filters"
            :has-filters="$hasFilters"
            :action="route('revit.families.index')"
            :categories="$categories"
            :brands="$brands"
        />
    </div>
</x-layouts.site>