<x-layouts.account title="Favoritos">
    <x-account.page-header
        title="Favoritos"
        description="Famílias que você salvou para acessar depois." />

    @if ($favorites->isEmpty())
        <x-ui.empty-state
            icon="heart"
            title="Você ainda não tem favoritos"
            description="Toque no coração de uma família para salvá-la aqui.">
            <x-ui.button :href="route('revit.families.index')">Explorar famílias</x-ui.button>
        </x-ui.empty-state>
    @else
        <p class="text-sm text-muted">
            {{ number_format($favorites->total(), 0, ',', '.') }}
            {{ $favorites->total() === 1 ? 'família salva' : 'famílias salvas' }}
        </p>

        {{-- Removing a favorite here only turns the heart off: the card stays
             until the next visit, so the person can undo a mistaken click. --}}
        <div class="mt-3 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @foreach ($favorites as $favorite)
                <x-site.revit.family-card :family="$favorite->family" />
            @endforeach
        </div>

        {{ $favorites->links('site.pagination') }}
    @endif
</x-layouts.account>