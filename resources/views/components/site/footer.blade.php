@php
$company  = config('showcase.company');
$person   = config('showcase.person');
$profiles = config('showcase.profiles');
$areas    = \App\Support\ShowcaseMenu::areas();
$link     = 'block py-1.5 text-sm leading-snug text-muted transition hover:text-primary';
@endphp

<footer class="border-t border-line bg-surface">
    {{-- Site map: services side by side, so every visitor sees both areas. --}}
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-x-6 gap-y-10 px-4 py-12 lg:grid-cols-4">
        @foreach ($areas as $area)
        <nav aria-label="{{ $area['heading'] }}">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink">
                <a href="{{ $area['url'] }}" class="hover:text-primary">{{ $area['heading'] }}</a>
            </h2>
            <ul class="mt-3">
                @foreach ($area['services'] as $service)
                <li><a href="{{ $service['url'] }}" class="{{ $link }}">{{ $service['name'] }}</a></li>
                @endforeach
            </ul>
        </nav>
        @endforeach

        <nav aria-label="{{ config('app.name') }}">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink">{{ config('app.name') }}</h2>
            <ul class="mt-3">
                <li><a href="{{ route('revit.families.index') }}" class="{{ $link }}">Famílias Revit</a></li>
                <li><a href="{{ route('services.index') }}" class="{{ $link }}">Todos os serviços</a></li>
                <li><a href="{{ route('quotes.create') }}" class="{{ $link }}">Solicitar orçamento</a></li>
                <li><a href="{{ route('contact.show') }}" class="{{ $link }}">Fale conosco</a></li>
            </ul>
        </nav>

        <div class="col-span-2 lg:col-span-1">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink">Quem faz</h2>
            <p class="mt-3 font-semibold text-ink">{{ $person['name'] }}</p>
            <ul class="mt-1 space-y-1 text-sm text-muted">
                <li>{{ $profiles['engineering']['title'] }} · {{ $profiles['engineering']['badge'] }}</li>
                <li>{{ $profiles['technology']['title'] }}</li>
            </ul>
            {{-- Badge instead of a plain line, so the icon doesn't float when the text wraps. --}}
            <p title="{{ $company['service_mode'] }}" class="mt-3 inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-primary-soft py-1.5 pl-2 pr-3 text-xs font-semibold text-on-primary-soft">
                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary">
                    @svg('heroicon-o-globe-americas', 'size-4')
                </span>
                {{ $company['service_mode_short'] }}
            </p>
            <div class="mt-3 flex gap-4 text-sm">
                @foreach ($person['links'] as $label => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener me" class="font-semibold text-primary hover:underline">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 text-sm text-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>

            <nav aria-label="Legal" class="flex gap-4">
                <a href="{{ route('legal.terms') }}" class="hover:underline">Termos de Uso</a>
                <a href="{{ route('legal.privacy') }}" class="hover:underline">Política de Privacidade</a>
            </nav>
        </div>
    </div>
</footer>
