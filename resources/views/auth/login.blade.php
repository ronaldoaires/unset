<x-layouts.site title="Entrar" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Entrar na sua conta</h1>
        <p class="mt-1 text-sm text-muted">
            Não tem conta?
            <a href="{{ route('register') }}" class="font-medium text-primary underline">Cadastre-se</a>
        </p>

        <x-auth.google-button class="mt-6" />

        <div class="my-5 flex items-center gap-3 text-xs text-subtle">
            <span class="h-px flex-1 bg-line"></span>
            ou entre com e-mail
            <span class="h-px flex-1 bg-line"></span>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6 rounded-card bg-surface p-6 shadow-card">
            @csrf

            <x-ui.input name="email" label="E-mail" type="email" autocomplete="email" required autofocus />
            <x-ui.input name="password" label="Senha" type="password" autocomplete="current-password" required />

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-line">
                    Lembrar de mim
                </label>
                <a href="{{ route('password.request') }}" class="text-primary underline">Esqueci minha senha</a>
            </div>

            <x-ui.button class="w-full">Entrar</x-ui.button>
        </form>

        {{-- Shown only when the last attempt was blocked because the e-mail is not confirmed --}}
        @if (session('unverified_email'))
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm text-amber-900">Não recebeu o e-mail de ativação? Confira o spam ou peça um novo.</p>
                <x-auth.resend-verification :email="session('unverified_email')" class="mt-3" />
            </div>
        @endif
    </div>
</x-layouts.site>