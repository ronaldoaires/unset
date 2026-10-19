<x-layouts.site title="Sua assinatura" :noindex="true">
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <div class="rounded-card bg-surface p-6 text-center shadow-card">
            @if ($subscription->isValid())
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success-soft text-success">
                    @svg('heroicon-o-check-circle', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Assinatura ativada!</h1>
                <p class="mt-3 text-sm text-muted">
                    Seu plano <strong class="text-ink">{{ $subscription->plan->name }}</strong> já está liberado.
                </p>

                <div class="mt-6 flex flex-col gap-3">
                    <x-ui.button :href="route('revit.families.index')">Explorar famílias</x-ui.button>
                    <x-ui.button :href="route('account.subscription')" variant="outline">Ver minha assinatura</x-ui.button>
                </div>
            @elseif ($subscription->status === 'pending')
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-primary-soft text-primary">
                    @svg('heroicon-o-clock', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Estamos confirmando seu pagamento</h1>
                <p class="mt-3 text-sm text-muted">
                    Isso pode levar alguns minutos. Assim que for confirmado, seu plano será liberado e você verá a assinatura na sua conta.
                </p>

                <x-ui.button :href="route('account.subscription')" class="mt-6">Ver minha assinatura</x-ui.button>
            @else
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-red-100 text-red-700">
                    @svg('heroicon-o-exclamation-triangle', 'size-8')
                </span>

                <h1 class="mt-4 text-2xl font-semibold">Não foi possível ativar</h1>
                <p class="mt-3 text-sm text-muted">
                    O pagamento não foi concluído e nada foi cobrado. Você pode tentar de novo.
                </p>

                <x-ui.button :href="route('checkout.show', ['plan' => $subscription->plan->slug])" class="mt-6">Tentar novamente</x-ui.button>
            @endif
        </div>
    </div>
</x-layouts.site>