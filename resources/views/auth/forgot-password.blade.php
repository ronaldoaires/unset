<x-layouts.site title="Esqueci minha senha" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Esqueci minha senha</h1>
        <p class="mt-1 text-sm text-muted">
            Informe seu e-mail e enviaremos um link para você criar uma nova senha.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-6 rounded-card bg-surface p-6 shadow-card">
            @csrf
            <x-auth.bot-trap />

            <x-ui.input name="email" label="E-mail" type="email" autocomplete="email" required autofocus />

            <x-ui.button class="w-full">Enviar link</x-ui.button>
        </form>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('login') }}" class="text-primary underline">Voltar para o login</a>
        </p>
    </div>
</x-layouts.site>