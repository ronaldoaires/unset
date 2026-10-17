@props(['family', 'labeled' => false])

@php
    $favorited = auth()->check() && in_array((int) $family->id, auth()->user()->favoriteFamilyIds(), true);

    $base = 'group inline-flex items-center justify-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';

    // Round icon over the card image, or a full-width labeled button on the family page.
    $look = $labeled
        ? 'min-h-12 w-full gap-2 rounded-control border border-line bg-surface px-5 text-sm font-semibold text-ink hover:bg-canvas aria-pressed:border-red-200 aria-pressed:bg-red-50 aria-pressed:text-red-600'
        : 'size-11 rounded-full bg-surface/95 text-subtle shadow-card hover:text-red-600 aria-pressed:text-red-600';
@endphp

{{-- Classes passed by the caller position the control (e.g. over the card image). --}}
<div {{ $attributes }}>
    @auth
        {{-- Same URL for both actions: the hidden _method is enabled only while the
             family is a favorite, so the form removes it instead of adding it.
             data-no-loading keeps the global submit spinner off this button. --}}
        <form
            method="POST"
            action="{{ route('revit.families.favorite.store', ['family' => $family->slug]) }}"
            data-favorite-form
            data-no-loading
        >
            @csrf
            <input type="hidden" name="_method" value="DELETE" @disabled(! $favorited)>

            <button
                type="submit"
                aria-pressed="{{ $favorited ? 'true' : 'false' }}"
                @unless ($labeled)
                    aria-label="{{ $favorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}"
                    data-label-on="Remover dos favoritos"
                    data-label-off="Adicionar aos favoritos"
                @endunless
                class="{{ $base }} {{ $look }}"
            >
                @svg('heroicon-o-heart', 'size-5 group-aria-pressed:hidden')
                @svg('heroicon-s-heart', 'hidden size-5 group-aria-pressed:block')

                @if ($labeled)
                    <span class="group-aria-pressed:hidden">Favoritar</span>
                    <span class="hidden group-aria-pressed:inline">Favoritado</span>
                @endif
            </button>
        </form>
    @else
        {{-- nofollow: crawlers should not follow one login redirect per card. --}}
        <a
            href="{{ route('revit.families.favorite.prompt', ['family' => $family->slug]) }}"
            rel="nofollow"
            @unless ($labeled) aria-label="Entrar para favoritar" @endunless
            class="{{ $base }} {{ $look }}"
        >
            @svg('heroicon-o-heart', 'size-5')
            @if ($labeled)
                Favoritar
            @endif
        </a>
    @endauth
</div>