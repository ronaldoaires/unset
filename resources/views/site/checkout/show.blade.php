@php
$money = fn ($value) => number_format((float) $value, 2, ',', '.');
$isYearly = $cycle === 'anual';
$discount = $plan->yearlyDiscountPercent();
@endphp

<x-layouts.site :title="'Assinar ' . $plan->name" :noindex="true">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Finalizar assinatura</h1>
        <p class="mt-1 text-sm text-muted">Confira o plano e complete seus dados para seguir ao pagamento.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">

            {{-- Data --}}
            <form
                method="POST"
                action="{{ route('checkout.store', ['plan' => $plan->slug]) }}"
                class="space-y-6 rounded-card bg-surface p-6 shadow-card lg:col-span-3">
                @csrf
                <input type="hidden" name="ciclo" value="{{ $cycle }}">

                <div>
                    <h2 class="text-lg font-semibold text-ink">Seus dados</h2>
                    <p class="mt-1 text-sm text-muted">Pedimos só o necessário para identificar seus pagamentos e verificar sua conta.</p>
                </div>

                <x-ui.input
                    name="cpf"
                    label="CPF"
                    :value="$cpf"
                    data-mask="cpf"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="000.000.000-00"
                    hint="Usado apenas para identificar seus pagamentos. Fica armazenado de forma criptografada."
                    required />

                <x-ui.input
                    name="phone"
                    label="Telefone / WhatsApp"
                    type="tel"
                    :value="$phone"
                    data-mask="phone"
                    inputmode="numeric"
                    autocomplete="tel-national"
                    placeholder="(66) 99999-9999"
                    hint="Usado apenas para verificar sua conta e liberar o acesso ao grupo de assinantes."
                    required />

                @error('payment')
                <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
                @enderror

                @if ($needsTerms)
                <div>
                    <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 size-5 shrink-0 rounded accent-primary">
                        <span>
                            Li e aceito os
                            <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-medium text-primary underline">Termos de Uso</a>
                            e a
                            <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-medium text-primary underline">Política de Privacidade</a>.
                        </span>
                    </label>

                    @error('terms')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <x-ui.button size="lg" class="w-full">Ir para o pagamento</x-ui.button>
            </form>

            {{-- Order summary --}}
            <aside class="h-fit rounded-card bg-surface p-6 shadow-card lg:col-span-2">
                <p class="text-sm text-subtle">Plano escolhido</p>
                <h2 class="mt-0.5 text-xl font-bold text-ink">{{ $plan->name }}</h2>

                <div class="mt-4 border-t border-line pt-4">
                    @if ($isYearly)
                    <p class="text-3xl font-bold leading-none text-ink">
                        <span class="text-base font-semibold text-subtle">R$</span>
                        {{ $money($plan->price_yearly) }}
                        <span class="text-base font-medium text-subtle">/ano</span>
                    </p>
                    <p class="mt-2 text-sm text-muted">
                        Equivale a R$ {{ $plan->yearlyMonthlyEquivalent() }} por mês
                        @if ($discount > 0)
                        <span class="ml-1 rounded-full bg-success-soft px-2 py-0.5 text-xs font-bold text-success">-{{ $discount }}%</span>
                        @endif
                    </p>
                    @else
                    <p class="text-3xl font-bold leading-none text-ink">
                        <span class="text-base font-semibold text-subtle">R$</span>
                        {{ $money($plan->price_monthly) }}
                        <span class="text-base font-medium text-subtle">/mês</span>
                    </p>
                    @endif

                    @if ($plan->trial_days > 0)
                    <p class="mt-2 text-sm font-medium text-primary">{{ $plan->trial_days }} dias de teste grátis</p>
                    @endif

                    @if ($canYearly)
                    @if ($isYearly)
                    <a
                        href="{{ route('checkout.show', ['plan' => $plan->slug, 'ciclo' => 'mensal']) }}"
                        class="mt-3 inline-block text-sm font-medium text-primary underline">
                        Prefiro pagar mensalmente
                    </a>
                    @elseif ($discount > 0)
                    <x-ui.alert icon="sparkles" class="mt-4">
                        Economize {{ $discount }}% no plano anual.
                        <a
                            href="{{ route('checkout.show', ['plan' => $plan->slug, 'ciclo' => 'anual']) }}"
                            class="font-bold underline">
                            Trocar para o anual
                        </a>
                    </x-ui.alert>
                    @endif
                    @endif
                </div>

                <ul class="mt-4 space-y-2 border-t border-line pt-4 text-sm text-muted">
                    @foreach ($plan->benefits() as $benefit)
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 size-5 shrink-0 text-success')
                        {{ $benefit }}
                    </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </div>
</x-layouts.site>