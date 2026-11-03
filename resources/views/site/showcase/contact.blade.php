<x-layouts.site
    title="Contato"
    description="Fale com a UnSet: dúvidas, parcerias ou orçamentos de projetos de engenharia e tecnologia. Atendimento para todo o Brasil.">

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <h1 class="text-3xl font-bold tracking-tight text-ink">Fale conosco</h1>
            <p class="mt-2 text-muted">Mande sua mensagem e respondemos {{ config('showcase.contact.reply_time') }}.</p>

            <div class="mt-6 space-y-4">
                <div class="rounded-card bg-surface p-6 shadow-card">
                    <h2 class="font-bold text-ink">Quer um orçamento?</h2>
                    <p class="mt-1 text-sm text-muted">O formulário de orçamento pergunta o que precisamos para responder mais rápido.</p>
                    <x-ui.button :href="route('quotes.create')" class="mt-4 w-full">Solicitar orçamento</x-ui.button>
                </div>

                <div class="rounded-card bg-surface p-6 shadow-card">
                    <h2 class="font-bold text-ink">WhatsApp</h2>
                    <p class="mt-1 text-sm text-muted">Para uma conversa rápida.</p>
                    <form method="POST" action="{{ route('whatsapp') }}" target="_blank" data-no-loading class="mt-4">
                        @csrf
                        <x-ui.button variant="tonal" class="w-full">
                            @svg('heroicon-o-chat-bubble-left-right', 'size-5')
                            Falar no WhatsApp
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </div>

        <x-site.contact-form id="fale-conosco" class="h-fit rounded-card bg-surface p-6 shadow-card sm:p-8 lg:col-span-3" />
    </div>
</x-layouts.site>
