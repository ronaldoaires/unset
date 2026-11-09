<x-layouts.site
    title="Texturas PBR gratuitas para Revit"
    description="Texturas PBR (cor, relevo e rugosidade) de pedra, madeira, cerâmica e mais, no tamanho real. Baixe pelo site ou aplique direto no Revit com o plugin UnSet BIM."
    :canonical="$canonical"
    :noindex="$hasFilters"
    :json-ld="$jsonLd"
>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <h1 class="mt-4 text-3xl font-bold leading-tight tracking-tight text-ink">Texturas PBR</h1>
        <p class="mt-2 max-w-3xl leading-relaxed text-muted">
            Texturas sem emenda com cor, relevo e rugosidade, cadastradas no <strong class="text-ink">tamanho real</strong>:
            o material já entra no Revit na escala certa.
        </p>

        <x-site.revit.plugin-textures-cta class="mt-6" />

        {{-- GET form: every filter lives in the URL, so any result can be shared. --}}
        <form method="GET" action="{{ route('revit.textures.index') }}" class="mt-6 rounded-card bg-surface p-4 shadow-card sm:p-6">
            <div class="space-y-4">
                <x-ui.input name="q" label="Buscar" type="search" :value="$filters['q']" placeholder="Ex: pedra, madeira, porcelanato" />

                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.select name="categoria" label="Categoria">
                        <option value="" @selected($filters['categoria'] === '')>Todas as categorias</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters['categoria'] === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="tipo" label="Tipo de material">
                        <option value="" @selected($filters['tipo'] === '')>Todos os tipos</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($filters['tipo'] === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="ordenar" label="Ordenar por">
                        @foreach (['recentes' => 'Mais recentes', 'baixadas' => 'Mais baixadas', 'nome' => 'Nome (A-Z)'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['ordenar'] === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex min-h-12 cursor-pointer items-center gap-3 text-sm font-medium text-ink">
                    <input type="checkbox" name="gratuita" value="1" @checked($filters['gratuita']) class="size-5 rounded accent-primary">
                    Somente gratuitas
                </label>

                <div class="flex gap-3">
                    @if ($hasFilters)
                        <x-ui.button :href="route('revit.textures.index')" variant="ghost">Limpar filtros</x-ui.button>
                    @endif
                    <x-ui.button class="flex-1 sm:flex-none">Filtrar</x-ui.button>
                </div>
            </div>
        </form>

        @if ($textures->isEmpty())
            @if ($hasFilters)
                <x-ui.empty-state class="mt-6" icon="magnifying-glass" title="Nenhuma textura encontrada" description="Tente outro termo de busca ou remova alguns filtros.">
                    <x-ui.button :href="route('revit.textures.index')">Limpar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state class="mt-6" icon="swatch" title="As primeiras texturas estão chegando" description="Estamos publicando conteúdo novo todos os dias. Volte em breve." />
            @endif
        @else
            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($textures as $texture)
                    <x-site.revit.texture-card :texture="$texture" />
                @endforeach
            </div>

            {{ $textures->links('components.site.pagination') }}
        @endif
    </div>
</x-layouts.site>
