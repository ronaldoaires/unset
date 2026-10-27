<x-layouts.site title="Autorizar o plugin" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="text-2xl font-semibold">Autorizar o plugin?</h1>
        <p class="mt-1 text-sm text-muted">
            O plugin do UnSet BIM está pedindo acesso à sua conta.
        </p>

        <div class="mt-6 space-y-6 rounded-card bg-surface p-6 shadow-card">
            <div class="text-center">
                <p class="text-sm text-muted">Confira se é o mesmo código mostrado no plugin</p>
                <p class="mt-2 font-mono text-3xl font-semibold tracking-widest">{{ $authorization->formattedUserCode() }}</p>
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Dispositivo</dt>
                    <dd class="text-right font-medium">{{ $authorization->device_name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Conta</dt>
                    <dd class="text-right font-medium">{{ auth()->user()->email }}</dd>
                </div>
            </dl>

            <x-ui.alert tone="warning">
                Só autorize se foi você quem iniciou o login no Revit agora. Nunca autorize um código enviado por outra pessoa.
            </x-ui.alert>

            <form method="POST" action="{{ route('device.update') }}" class="flex flex-col gap-3 sm:flex-row-reverse">
                @csrf
                <input type="hidden" name="codigo" value="{{ $authorization->user_code }}">

                <x-ui.button name="action" value="approve" class="w-full sm:flex-1">Autorizar</x-ui.button>
                <x-ui.button name="action" value="deny" variant="outline" class="w-full sm:flex-1">Negar</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.site>
