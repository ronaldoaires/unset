@php($title = $approved ? 'Plugin conectado' : 'Acesso negado')

<x-layouts.site :title="$title" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 text-center shadow-card">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>

            <p class="mt-3 text-sm text-muted">
                @if ($approved)
                    Pronto! Pode voltar para o Revit: o plugin vai entrar na sua conta em alguns segundos.
                @else
                    O plugin não foi conectado à sua conta. Se não foi você quem pediu, nada mais precisa ser feito.
                @endif
            </p>

            <x-ui.button :href="route('home')" variant="outline" class="mt-6">Ir para o site</x-ui.button>
        </div>
    </div>
</x-layouts.site>
