<x-layouts.site title="Redefinir senha" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Criar nova senha</h1>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <x-ui.input name="email" label="E-mail" type="email" :value="$email" autocomplete="email" required />
            <x-ui.input
                name="password"
                label="Nova senha"
                type="password"
                autocomplete="new-password"
                hint="Mínimo de 8 caracteres, com letras e números."
                required
                autofocus
            />
            <x-ui.input name="password_confirmation" label="Confirmar nova senha" type="password" autocomplete="new-password" required />

            <x-ui.button class="w-full">Salvar nova senha</x-ui.button>
        </form>
    </div>
</x-layouts.site>