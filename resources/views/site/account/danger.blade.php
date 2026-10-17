<x-layouts.account title="Encerrar conta">
    <x-account.page-header
        title="Encerrar conta"
        description="Peça a exclusão da sua conta e dos seus dados pessoais." />

    <div class="space-y-6">
        @if ($scheduledFor)
            <div class="rounded-card border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                <p class="font-semibold">Encerramento agendado para {{ $scheduledFor->format('d/m/Y') }}.</p>
                <p class="mt-1">Até lá sua conta continua funcionando normalmente. Se mudou de ideia, cancele o pedido.</p>

                <form method="POST" action="{{ route('account.danger.cancel') }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="outline">Cancelar pedido</x-ui.button>
                </form>
            </div>
        @else
            <section class="rounded-card bg-surface p-6 shadow-card">
                <h2 class="text-lg font-semibold text-ink">O que acontece</h2>

                <ul class="mt-3 space-y-2 text-sm text-muted">
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-clock', 'mt-0.5 size-4 shrink-0 text-primary')
                        Sua conta continua ativa por 30 dias, e você pode cancelar o pedido nesse prazo.
                    </li>
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-trash', 'mt-0.5 size-4 shrink-0 text-primary')
                        Depois disso, seus dados pessoais, favoritos e histórico de downloads são apagados de forma definitiva.
                    </li>
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-banknotes', 'mt-0.5 size-4 shrink-0 text-primary')
                        Registros de pagamentos e compras podem ser mantidos sem identificação pessoal, para fins fiscais e contábeis.
                    </li>
                </ul>
            </section>

            @if ($blocker)
                <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    {{ $blocker }}
                </div>
            @else
                <form method="POST" action="{{ route('account.danger.store') }}" class="space-y-6 rounded-card bg-surface p-6 shadow-card">
                    @csrf

                    @if ($hasPassword)
                        <x-ui.input name="password" label="Sua senha" type="password" autocomplete="current-password" required />
                    @endif

                    <x-ui.input
                        name="confirmation"
                        label="Digite ENCERRAR para confirmar"
                        autocomplete="off"
                        required />

                    <x-ui.button variant="danger" class="w-full sm:w-auto">Encerrar minha conta</x-ui.button>
                </form>
            @endif
        @endif
    </div>
</x-layouts.account>