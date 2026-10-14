<x-layouts.site title="Cadastro realizado" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 text-center shadow-sm">
            <h1 class="text-2xl font-semibold">Quase lá!</h1>

            <p class="mt-3 text-sm text-zinc-600">
                O link de ativação foi enviado para <strong>{{ $email }}</strong>.
                Você só consegue entrar depois de ativar o cadastro clicando nele.
            </p>

            <p class="mt-3 text-xs text-zinc-500">
                Não encontrou? Confira a caixa de spam ou peça um novo link abaixo.
            </p>

            <x-auth.resend-verification :email="$email" class="mt-4" />

            <a href="{{ route('login') }}" class="mt-4 inline-block text-sm text-amber-700 underline">
                Já ativei, ir para o login
            </a>
        </div>
    </div>
</x-layouts.site>