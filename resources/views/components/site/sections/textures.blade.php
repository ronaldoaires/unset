@props(['textures'])

@if ($textures->isNotEmpty())
{{-- Home: newest PBR textures, shown as material balls, with the plugin shortcut. --}}
<section id="texturas" class="bg-canvas">
    <div class="mx-auto max-w-6xl px-4 pb-16 pt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold tracking-tight text-ink">Texturas PBR novas</h2>
                <p class="mt-2 text-lg text-muted">
                    Cadastradas no tamanho real. Baixe pelo site ou aplique no Revit em 3 cliques com o
                    <a href="{{ route('plugin.download') }}" class="font-semibold text-primary hover:underline">plugin grátis</a>.
                </p>
            </div>

            <a href="{{ route('revit.textures.index') }}" class="inline-flex min-h-12 items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                Ver todas as texturas
                @svg('heroicon-o-arrow-right', 'size-4')
            </a>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($textures as $texture)
                <x-site.revit.texture-card :texture="$texture" />
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <x-ui.button :href="route('revit.textures.index')" variant="tonal" size="lg">
                Ver mais texturas
                @svg('heroicon-o-arrow-right', 'size-5')
            </x-ui.button>
        </div>
    </div>
</section>
@endif
