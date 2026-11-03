@php
$company  = config('showcase.company');
$person   = config('showcase.person');
$profiles = config('showcase.profiles');
$areas    = \App\Support\ShowcaseMenu::areas();
// Pages that already are a contact form don't get a second one in the footer.
$withForm = ! request()->routeIs('contact.show', 'quotes.create');
$link     = 'inline-flex min-h-9 items-center text-sm text-muted transition hover:text-primary';
@endphp

<footer class="border-t border-line bg-surface">
    {{-- "Fale conosco": the lowest-friction way to get in touch, on every page. --}}
    @if ($withForm)
    <div class="border-b border-line">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold tracking-tight text-ink">Fale conosco</h2>
                <p class="mt-2 text-muted">Dúvidas, parcerias ou um projeto em mente? Mande uma mensagem e respondemos {{ config('showcase.contact.reply_time') }}.</p>

                <x-ui.button :href="route('quotes.create')" variant="tonal" class="mt-6">
                    @svg('heroicon-o-document-text', 'size-5')
                    Solicitar orçamento completo
                </x-ui.button>
            </div>

            <x-site.contact-form class="rounded-card bg-canvas p-6 lg:col-span-3" />
        </div>
    </div>
    @endif

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
                <li><a href="{{ route('portfolio.index') }}" class="{{ $link }}">Portfólio</a></li>
                <li><a href="{{ route('services.index') }}" class="{{ $link }}">Todos os serviços</a></li>
                <li><a href="{{ route('quotes.create') }}" class="{{ $link }}">Solicitar orçamento</a></li>
                <li><a href="{{ route('contact.show') }}" class="{{ $link }}">Contato</a></li>
            </ul>
        </nav>

        <div class="col-span-2 lg:col-span-1">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink">Quem faz</h2>
            <p class="mt-3 font-semibold text-ink">{{ $person['name'] }}</p>
            <ul class="mt-1 space-y-1 text-sm text-muted">
                <li>{{ $profiles['engineering']['title'] }} · {{ $profiles['engineering']['badge'] }}</li>
                <li>{{ $profiles['technology']['title'] }}</li>
                <li class="flex items-center gap-1.5 pt-1">
                    @svg('heroicon-o-globe-americas', 'size-4 text-primary')
                    {{ $company['service_mode'] }}
                </li>
            </ul>
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
