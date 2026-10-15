<x-layouts.site title="Criar conta" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Criar conta</h1>
        <p class="mt-1 text-sm text-zinc-600">
            Já tem conta?
            <a href="{{ route('login') }}" class="font-medium text-amber-700 underline">Entrar</a>
        </p>

        <x-auth.google-button label="Cadastrar com o Google" class="mt-6" />

        <div class="my-5 flex items-center gap-3 text-xs text-zinc-500">
            <span class="h-px flex-1 bg-zinc-200"></span>
            ou cadastre com e-mail
            <span class="h-px flex-1 bg-zinc-200"></span>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf

            <x-ui.input name="name" label="Nome" autocomplete="name" required autofocus />
            <x-ui.input name="email" label="E-mail" type="email" autocomplete="email" required />
            <x-ui.input
                name="password"
                label="Senha"
                type="password"
                autocomplete="new-password"
                hint="Mínimo de 8 caracteres, com letras e números."
                required
            />
            <x-ui.input name="password_confirmation" label="Confirmar senha" type="password" autocomplete="new-password" required />

            <x-ui.button class="w-full">Criar conta</x-ui.button>
        </form>
    </div>
</x-layouts.site>