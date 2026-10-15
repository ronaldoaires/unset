<x-layouts.site title="Link inválido ou expirado" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 shadow-card">
            <h1 class="text-center text-2xl font-semibold">Link inválido ou expirado</h1>

            <p class="mt-3 text-center text-sm text-muted">
                Este link de ativação não é mais válido. Informe o e-mail do seu cadastro para receber um novo.
            </p>

            @auth
                <x-ui.button :href="route('verification.notice')" class="mt-6 w-full">Pedir novo link</x-ui.button>
            @else
                <x-auth.resend-verification class="mt-6" />
            @endauth
        </div>
    </div>
</x-layouts.site>