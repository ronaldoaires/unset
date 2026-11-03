{{-- Conversion page: track visits to /mensagem-enviada in Analytics or Ads. --}}
<x-layouts.site
    :title="$type === \App\Enums\LeadType::Quote ? 'Pedido de orçamento enviado' : 'Mensagem enviada'"
    :noindex="true">

    <div class="mx-auto max-w-2xl px-4 py-16">
        <div class="rounded-card bg-surface p-8 text-center shadow-card sm:p-10">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-success-soft text-success">
                @svg('heroicon-o-check', 'size-8')
            </span>

            <h1 class="mt-6 text-2xl font-bold tracking-tight text-ink">
                {{ $type === \App\Enums\LeadType::Quote ? 'Pedido de orçamento enviado!' : 'Mensagem enviada!' }}
            </h1>

            <p class="mt-3 text-muted">
                Recebemos tudo certinho e vamos responder {{ config('showcase.contact.reply_time') }}.
                Enviamos uma confirmação para o seu e-mail. Se não encontrar, olhe na caixa de spam.
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <x-ui.button :href="route('services.index')" variant="tonal">Ver os serviços</x-ui.button>
                <x-ui.button :href="route('home')" variant="outline">Voltar ao início</x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.site>
