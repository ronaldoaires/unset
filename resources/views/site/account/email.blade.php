<x-layouts.account title="E-mail">
    <x-account.page-header
        title="E-mail"
        description="Troque o e-mail da sua conta. Você precisa confirmar o novo endereço." />

    <div class="space-y-6">
        @if ($user->pending_email)
            <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p>
                    Aguardando confirmação de <strong>{{ $user->pending_email }}</strong>.
                    Clique no link que enviamos para esse endereço (vale por 60 minutos).
                    Para receber um novo link, preencha o formulário abaixo de novo.
                </p>

                <form method="POST" action="{{ route('account.email.cancel') }}" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="outline">Cancelar pedido</x-ui.button>
                </form>
            </div>
        @endif

        <form method="POST" action="{{ route('account.email.update') }}" class="space-y-6 rounded-card bg-surface p-6 shadow-card">
            @csrf
            @method('PUT')

            <div>
                <p class="text-sm font-medium text-ink">E-mail atual</p>
                <p class="mt-1.5 text-sm text-muted">{{ $user->email }}</p>
            </div>

            <x-ui.input name="email" label="Novo e-mail" type="email" autocomplete="email" required />

            @if ($hasPassword)
                <x-ui.input
                    name="password"
                    label="Sua senha"
                    type="password"
                    autocomplete="current-password"
                    hint="Pedimos a senha para confirmar que é você."
                    required
                />
            @else
                <p class="text-sm text-muted">
                    Sua conta entra pelo Google, e isso não muda. Vamos avisar o e-mail atual sobre o pedido.
                </p>
            @endif

            <x-ui.button class="w-full sm:w-auto">Enviar link de confirmação</x-ui.button>
        </form>
    </div>
</x-layouts.account>