@php
$money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
@endphp

<x-layouts.account title="Assinatura">
    <x-account.page-header
        title="Assinatura"
        description="Seu plano, benefícios e histórico de pagamentos." />

    <div class="space-y-6">

        @error('cancel')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
        @enderror

        {{-- Current plan --}}
        @if ($subscription)
        <section class="rounded-card bg-surface p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-subtle">{{ $hasAccess ? 'Plano atual' : 'Último plano' }}</p>
                    <h2 class="mt-0.5 text-2xl font-bold tracking-tight text-ink">{{ $subscription->plan->name }}</h2>
                </div>

                <x-ui.badge :tone="$subscription->statusTone()">{{ $subscription->statusLabel() }}</x-ui.badge>
            </div>

            @if ($message = $subscription->statusMessage())
            <p class="mt-3 text-sm text-muted">{{ $message }}</p>
            @endif

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-subtle">Cobrança</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $subscription->isYearly() ? 'Anual' : 'Mensal' }}</dd>
                </div>

                <div>
                    <dt class="text-subtle">Início</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $subscription->started_at->format('d/m/Y') }}</dd>
                </div>

                @if ($subscription->last_payment_at)
                <div>
                    <dt class="text-subtle">Último pagamento</dt>
                    <dd class="mt-0.5 font-medium text-ink">
                        {{ $money($subscription->last_payment_amount) }}
                        <span class="font-normal text-muted">em {{ $subscription->last_payment_at->format('d/m/Y') }}</span>
                    </dd>
                </div>
                @endif

                @if ($subscription->canceled_at)
                <div>
                    <dt class="text-subtle">Cancelada em</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $subscription->canceled_at->format('d/m/Y') }}</dd>
                </div>
                @endif
            </dl>

            @if ($hasAccess)
            <div class="mt-5 border-t border-line pt-5">
                <p class="text-sm font-medium text-ink">O que seu plano inclui</p>

                <ul class="mt-2 space-y-1.5 text-sm text-muted">
                    @foreach ($subscription->plan->benefits() as $benefit)
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 size-4 shrink-0 text-success')
                        {{ $benefit }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if ($subscription->canBeCanceled())
            {{-- <details> is the confirmation step and works without JavaScript. --}}
            <details class="mt-5 border-t border-line pt-5">
                <summary class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Cancelar assinatura</summary>

                <form method="POST" action="{{ route('account.subscription.cancel') }}" class="mt-4 space-y-4 rounded-control bg-canvas p-4">
                    @csrf
                    @method('DELETE')

                    <p class="text-sm text-muted">
                        Não haverá novas cobranças.
                        @if ($hasAccess && $subscription->renews_at)
                        Você continua com os benefícios até <strong class="text-ink">{{ $subscription->renews_at->format('d/m/Y') }}</strong>.
                        @endif
                    </p>

                    <x-ui.select name="reason" label="O que motivou o cancelamento? (opcional)">
                        <option value="">Prefiro não informar</option>
                        @foreach ($cancelReasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.button variant="danger" class="w-full sm:w-auto">Confirmar cancelamento</x-ui.button>
                </form>
            </details>
            @endif
        </section>
        @else
        <x-ui.empty-state
            icon="star"
            title="Você não tem um plano ativo"
            description="Assine um plano para liberar mais downloads e módulos." />
        @endif

        {{-- Monthly quota: only while the subscription grants access --}}
        @if ($quota)
        <section class="rounded-card bg-surface p-6 shadow-card">
            <h2 class="text-lg font-semibold text-ink">Downloads de famílias pagas neste mês</h2>

            @if ($quota['limit'] === null)
            <p class="mt-2 text-sm text-muted">Seu plano não tem limite mensal de downloads.</p>
            @else
            @php
            $percent = min(100, (int) round($quota['used'] / max(1, $quota['limit']) * 100));
            @endphp

            <p class="mt-2 text-sm text-muted">
                Você usou <strong class="text-ink">{{ $quota['used'] }}</strong> de <strong class="text-ink">{{ $quota['limit'] }}</strong>.
            </p>

            <div
                class="mt-3 h-2.5 overflow-hidden rounded-full bg-canvas"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="{{ $quota['limit'] }}"
                aria-valuenow="{{ min($quota['used'], $quota['limit']) }}">
                <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
            </div>

            <p class="mt-3 text-xs text-subtle">
                Famílias gratuitas, compras avulsas e baixar de novo uma família já baixada neste mês não consomem a cota.
                A cota volta em {{ $quota['resets_at']->format('d/m/Y') }}.
            </p>
            @endif
        </section>
        @endif

        {{-- Payments --}}
        <section id="pagamentos" class="rounded-card bg-surface p-6 shadow-card">
            <h2 class="text-lg font-semibold text-ink">Pagamentos</h2>

            @if ($payments->isEmpty())
            <p class="mt-3 text-sm text-muted">Nenhum pagamento registrado.</p>
            @else
            <ul class="mt-2 divide-y divide-line">
                @foreach ($payments as $payment)
                <li class="flex items-center justify-between gap-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ $payment->subscription?->plan?->name ?? 'Assinatura' }}
                        </p>
                        <p class="text-xs text-subtle">
                            {{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}
                            · {{ $payment->paymentMethodLabel() }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-sm font-semibold text-ink">{{ $money($payment->amount) }}</span>
                        <x-ui.badge :tone="$payment->statusTone()">{{ $payment->statusLabel() }}</x-ui.badge>
                    </div>
                </li>
                @endforeach
            </ul>

            {{ $payments->links('site.pagination') }}
            @endif
        </section>

        {{-- One-off purchases live on their own page --}}
        <p class="text-sm text-muted">
            Compras avulsas de famílias ficam em
            <a href="{{ route('account.purchases') }}" class="font-medium text-primary underline">Compras</a>.
        </p>
    </div>
</x-layouts.account>