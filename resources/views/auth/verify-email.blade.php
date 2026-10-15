<x-layouts.site title="Verifique seu e-mail" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Verifique seu e-mail</h1>
        <p class="mt-2 text-sm text-muted">
            Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>.
            Clique nele para ativar sua conta. Confira também a caixa de spam.
        </p>

        <div class="mt-6 space-y-3 rounded-card bg-surface p-6 shadow-card">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button class="w-full">Reenviar e-mail de confirmação</x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-sm text-muted underline">Sair</button>
            </form>
        </div>
    </div>
</x-layouts.site>