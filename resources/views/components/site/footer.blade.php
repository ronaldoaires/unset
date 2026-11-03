@php
$company = config('showcase.company');
$responsible = config('showcase.responsible');
// Pages that already are a contact form don't get a second one in the footer.
$withForm = ! request()->routeIs('contact.show', 'quotes.create');
@endphp

<footer class="border-t border-line bg-surface">
    {{-- "Fale conosco" on every page: the lowest-friction way to get in touch. --}}
    @if ($withForm)
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold tracking-tight text-ink">Fale conosco</h2>
            <p class="mt-2 text-muted">Dúvidas, parcerias ou um projeto em mente? Mande uma mensagem e respondemos {{ config('showcase.contact.reply_time') }}.</p>

            <ul class="mt-6 space-y-3 text-sm text-muted">
                <li class="flex items-center gap-2.5">
                    @svg('heroicon-o-document-text', 'size-5 text-primary')
                    <a href="{{ route('quotes.create') }}" class="font-semibold text-primary hover:underline">Solicitar orçamento completo</a>
                </li>
                <li class="flex items-center gap-2.5">
                    @svg('heroicon-o-briefcase', 'size-5 text-primary')
                    <a href="{{ route('services.index') }}" class="hover:underline">Serviços de engenharia e tecnologia</a>
                </li>
                <li class="flex items-center gap-2.5">
                    @svg('heroicon-o-shield-check', 'size-5 text-primary')
                    {{ $responsible['role'] }}: {{ $responsible['name'] }} · {{ $responsible['registry'] }}
                </li>
                <li class="flex items-center gap-2.5">
                    @svg('heroicon-o-globe-americas', 'size-5 text-primary')
                    {{ $company['service_mode'] }}
                </li>
            </ul>
        </div>

        <x-site.contact-form class="rounded-card bg-canvas p-6 lg:col-span-3" />
    </div>
    @endif

    <div @class(['border-t border-line' => $withForm])>
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 text-sm text-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>

            <nav aria-label="Legal" class="flex gap-4">
                <a href="{{ route('contact.show') }}" class="hover:underline">Contato</a>
                <a href="{{ route('legal.terms') }}" class="hover:underline">Termos de Uso</a>
                <a href="{{ route('legal.privacy') }}" class="hover:underline">Política de Privacidade</a>
            </nav>
        </div>
    </div>
</footer>
