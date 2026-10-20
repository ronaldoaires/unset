@php
$money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
@endphp

<x-layouts.account title="Compras">
    <x-account.page-header
        title="Compras"
        description="Tudo o que você comprou avulso, para baixar de novo quando precisar." />

    {{-- GET form: the search lives in the URL, so a result can be bookmarked. --}}
    @if ($purchases->isNotEmpty() || $term !== '')
        <form method="GET" action="{{ route('account.purchases') }}" class="mb-4 flex items-end gap-3">
            <div class="flex-1">
                <x-ui.input name="q" label="Buscar" type="search" :value="$term" placeholder="Nome do item" />
            </div>

            <x-ui.button>Buscar</x-ui.button>

            @if ($term !== '')
                <x-ui.button :href="route('account.purchases')" variant="ghost">Limpar</x-ui.button>
            @endif
        </form>
    @endif

    @if ($purchases->isEmpty())
        @if ($term !== '')
            <x-ui.empty-state
                icon="magnifying-glass"
                title="Nenhuma compra encontrada"
                description="Tente outro termo de busca.">
                <x-ui.button :href="route('account.purchases')">Limpar busca</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-ui.empty-state
                icon="shopping-bag"
                title="Você ainda não fez compras"
                description="As famílias que você comprar avulso aparecem aqui.">
                <x-ui.button :href="route('revit.families.index')">Explorar famílias</x-ui.button>
            </x-ui.empty-state>
        @endif
    @else
        <p class="text-sm text-muted">
            {{ number_format($purchases->total(), 0, ',', '.') }}
            {{ $purchases->total() === 1 ? 'compra' : 'compras' }}
        </p>

        <ul class="mt-3 space-y-3">
            @foreach ($purchases as $purchase)
                @php($family = $purchase->family)

                <li class="flex flex-col gap-4 rounded-card bg-surface p-4 shadow-card sm:flex-row sm:items-center">
                    <div class="relative aspect-[4/3] w-24 shrink-0 overflow-hidden rounded-control bg-canvas">
                        @if ($family?->thumbnail_url)
                            <img
                                src="{{ $family->thumbnail_url }}"
                                alt="{{ $family->name }} - família Revit"
                                width="96"
                                height="72"
                                loading="lazy"
                                class="absolute inset-0 size-full object-contain"
                            >
                        @else
                            <span class="absolute inset-0 flex items-center justify-center text-subtle">
                                @svg('heroicon-o-cube', 'size-7')
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-semibold text-ink">
                            @if ($family?->isPublished())
                                <a href="{{ route('revit.families.show', ['family' => $family->slug]) }}" class="hover:underline">{{ $family->name }}</a>
                            @else
                                {{ $family?->name ?? 'Família removida' }}
                            @endif
                        </h2>

                        <p class="mt-0.5 truncate text-xs text-subtle">
                            Família Revit
                            @if ($family?->category)
                                · {{ $family->category->name }}
                            @endif
                        </p>

                        <p class="mt-1.5 text-sm text-muted">
                            {{ ($purchase->paid_at ?? $purchase->created_at)->format('d/m/Y \à\s H:i') }}
                            · {{ $purchase->paymentMethodLabel() }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4 sm:w-64 sm:shrink-0 sm:flex-col sm:items-end sm:justify-center">
                        <div class="flex flex-col items-start gap-1 sm:items-end">
                            <span class="text-base font-semibold text-ink">{{ $money($purchase->amount) }}</span>
                            <x-ui.badge :tone="$purchase->statusTone()">{{ $purchase->statusLabel() }}</x-ui.badge>
                        </div>

                        @if ($family?->isPublished())
                            @if ($purchase->isPaid())
                                <x-ui.button :href="route('revit.families.download', ['family' => $family->slug])" class="sm:w-full">
                                    @svg('heroicon-o-arrow-down-tray', 'size-4')
                                    Baixar
                                </x-ui.button>
                            @elseif (in_array($purchase->status, ['pending', 'failed', 'cancelled'], true))
                                <x-ui.button :href="route('revit.families.purchase.show', ['family' => $family->slug])" variant="outline" class="sm:w-full">
                                    Pagar de novo
                                </x-ui.button>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        {{ $purchases->links('site.pagination') }}
    @endif
</x-layouts.account>