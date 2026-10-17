<x-layouts.site :title="$title" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 text-center shadow-card">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>

            <p class="mt-3 text-sm text-muted">{{ $message }}</p>

            @auth
                <x-ui.button :href="route('account.email')" class="mt-6">Ir para minha conta</x-ui.button>
            @else
                <x-ui.button :href="route('login')" class="mt-6">{{ $ok ? 'Entrar na minha conta' : 'Ir para o login' }}</x-ui.button>
            @endauth
        </div>
    </div>
</x-layouts.site>