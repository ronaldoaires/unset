@php
    $sortLabels = [
        'recentes'     => 'Mais recentes',
        'baixados'     => 'Mais baixadas',
        'visualizados' => 'Mais visualizadas',
        'nome'         => 'Nome (A-Z)',
    ];
@endphp

<x-layouts.site
    title="Famílias Revit para download"
    description="Biblioteca de famílias Revit (.rfa) gratuitas e premium: portas, janelas, mobiliário e muito mais, prontas para usar nos seus projetos BIM."
    :canonical="$canonical"
    :noindex="$hasFilters"
>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="text-3xl font-bold leading-tight tracking-tight text-ink">Famílias Revit</h1>
        <p class="mt-2 max-w-prose leading-relaxed text-muted">
            Biblioteca de famílias Revit (.rfa) gratuitas e premium, prontas para usar nos seus projetos.
        </p>

        {{-- GET form: every filter lives in the URL, so any result can be copied and shared. --}}
        <form method="GET" action="{{ route('revit.families.index') }}" class="mt-6 rounded-card bg-surface p-4 shadow-card sm:p-6">
            <div class="space-y-4">
                <x-ui.input name="q" label="Buscar" type="search" :value="$filters['q']" placeholder="Ex: porta, mesa, pilar" />

                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.select name="categoria" label="Categoria">
                        <option value="" @selected($filters['categoria'] === '')>Todas as categorias</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters['categoria'] === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="marca" label="Marca">
                        <option value="" @selected($filters['marca'] === '')>Todas as marcas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected($filters['marca'] === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="ordenar" label="Ordenar por">
                        @foreach ($sortLabels as $value => $label)
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
                        <x-ui.button :href="route('revit.families.index')" variant="ghost">Limpar filtros</x-ui.button>
                    @endif
                    <x-ui.button class="flex-1 sm:flex-none">Filtrar</x-ui.button>
                </div>
            </div>
        </form>

        @if ($families->isEmpty())
            @if ($hasFilters)
                <x-ui.empty-state class="mt-6" icon="magnifying-glass" title="Nenhuma família encontrada" description="Tente outro termo de busca ou remova alguns filtros.">
                    <x-ui.button :href="route('revit.families.index')">Limpar filtros</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state class="mt-6" icon="cube" title="As primeiras famílias chegam em breve" description="Estamos preparando a biblioteca. Volte em alguns dias." />
            @endif
        @else
            <p class="mt-6 text-sm text-muted">
                {{ number_format($families->total(), 0, ',', '.') }}
                {{ $families->total() === 1 ? 'família encontrada' : 'famílias encontradas' }}
            </p>

            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($families as $family)
                    <x-site.revit.family-card :family="$family" />
                @endforeach
            </div>

            {{ $families->links('site.pagination') }}
        @endif
    </div>
</x-layouts.site>