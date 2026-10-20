<x-layouts.site title="Sua compra" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 text-center shadow-card">
            @if ($purchase->isPaid())
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success-soft text-success">
                    @svg('heroicon-o-check-circle', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Compra confirmada!</h1>
                <p class="mt-3 text-sm text-muted">
                    A família <strong class="text-ink">{{ $purchase->family->name }}</strong> já está liberada na sua conta.
                </p>

                <div class="mt-6 flex flex-col gap-3">
                    <x-ui.button :href="route('revit.families.download', ['family' => $purchase->family->slug])">Baixar família</x-ui.button>
                    <x-ui.button :href="route('revit.families.show', ['family' => $purchase->family->slug])" variant="outline">Voltar para a família</x-ui.button>
                </div>
            @elseif ($purchase->status === 'pending')
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-primary-soft text-primary">
                    @svg('heroicon-o-clock', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Estamos confirmando seu pagamento</h1>
                <p class="mt-3 text-sm text-muted">
                    Se você pagou com Pix, pode levar alguns minutos. Assim que for confirmado, a família será liberada na sua conta.
                </p>

                <x-ui.button :href="route('revit.families.show', ['family' => $purchase->family->slug])" class="mt-6">Voltar para a família</x-ui.button>
            @else
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-red-100 text-red-700">
                    @svg('heroicon-o-exclamation-triangle', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Não foi possível concluir</h1>
                <p class="mt-3 text-sm text-muted">
                    O pagamento não foi aprovado e nada foi cobrado. Você pode tentar de novo.
                </p>

                <x-ui.button :href="route('revit.families.purchase.show', ['family' => $purchase->family->slug])" class="mt-6">Tentar novamente</x-ui.button>
            @endif
        </div>
    </div>
</x-layouts.site>