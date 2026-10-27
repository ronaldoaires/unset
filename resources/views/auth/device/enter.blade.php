<x-layouts.site title="Conectar o plugin" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Conectar o plugin</h1>
        <p class="mt-1 text-sm text-muted">
            Digite o código que aparece no plugin do UnSet BIM no Revit.
        </p>

        <form method="GET" action="{{ route('device.show') }}" class="mt-6 space-y-6 rounded-card bg-surface p-6 shadow-card">
            @if ($message = $error ?? $errors->first('codigo'))
                <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
            @endif

            <x-ui.input
                name="codigo"
                label="Código"
                placeholder="XXXX-XXXX"
                maxlength="9"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                class="text-center font-mono text-xl uppercase tracking-widest"
                :value="request('codigo')"
                required
                autofocus
            />

            <x-ui.button class="w-full">Continuar</x-ui.button>
        </form>
    </div>
</x-layouts.site>
