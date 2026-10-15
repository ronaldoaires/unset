<x-layouts.site title="E-mail confirmado" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 text-center shadow-card">
            <h1 class="text-2xl font-semibold">
                {{ $alreadyVerified ? 'E-mail já confirmado' : 'E-mail confirmado!' }}
            </h1>

            <p class="mt-3 text-sm text-muted">
                @if ($alreadyVerified)
                    Este e-mail já tinha sido confirmado antes. Sua conta está ativa.
                @else
                    Sua conta foi ativada com sucesso. Agora você já pode entrar no sistema.
                @endif
            </p>

            @auth
                <x-ui.button :href="route('home')" class="mt-6">Ir para o início</x-ui.button>
            @else
                <x-ui.button :href="route('login')" class="mt-6">Entrar na minha conta</x-ui.button>
            @endauth
        </div>
    </div>
</x-layouts.site>