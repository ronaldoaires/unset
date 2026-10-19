@props(['plans'])

@php
$money = fn ($value) => number_format((float) $value, 2, ',', '.');
$hasYearly = $plans->contains(fn ($plan) => $plan->price_yearly > 0);
@endphp

@if ($plans->isNotEmpty())
{{-- The monthly/yearly switch is pure CSS (radio inputs + has-[]), so it needs no JavaScript. --}}
<section id="planos" class="group/plans border-t border-line bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-ink">Escolha o plano ideal para os seus projetos</h2>
            <p class="mt-3 text-lg text-muted">
                Baixe famílias premium e libere os módulos da plataforma. Cancele quando quiser.
            </p>
        </div>

        @if ($hasYearly)
        <div class="mt-8 flex justify-center">
            <div class="inline-flex rounded-full bg-canvas p-1" role="radiogroup" aria-label="Ciclo de cobrança">
                <input id="cycle-monthly" name="cycle" type="radio" class="peer/monthly sr-only" checked>
                <label
                    for="cycle-monthly"
                    class="min-h-11 cursor-pointer rounded-full px-6 py-2.5 text-sm font-semibold text-muted transition peer-checked/monthly:bg-primary peer-checked/monthly:text-on-primary peer-focus-visible/monthly:ring-2 peer-focus-visible/monthly:ring-primary peer-focus-visible/monthly:ring-offset-2">
                    Mensal
                </label>

                <input id="cycle-yearly" name="cycle" type="radio" class="peer/yearly sr-only">
                <label
                    for="cycle-yearly"
                    class="min-h-11 cursor-pointer rounded-full px-6 py-2.5 text-sm font-semibold text-muted transition peer-checked/yearly:bg-primary peer-checked/yearly:text-on-primary peer-focus-visible/yearly:ring-2 peer-focus-visible/yearly:ring-primary peer-focus-visible/yearly:ring-offset-2">
                    Anual
                </label>
            </div>
        </div>
        @endif

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
            @php
            $canYearly = $hasYearly && $plan->price_yearly > 0;
            $discount = $plan->yearlyDiscountPercent();
            @endphp

            <article @class([ 'relative flex flex-col rounded-card bg-surface p-6 shadow-card' , 'ring-2 ring-primary'=> $plan->is_highlighted,
                'ring-1 ring-line' => ! $plan->is_highlighted,
                ])>
                @if ($plan->badge_label)
                <span class="absolute -top-3 left-6 rounded-full bg-accent px-3 py-1 text-xs font-bold text-white shadow-sm">
                    {{ $plan->badge_label }}
                </span>
                @endif

                <h3 class="text-xl font-bold text-ink">{{ $plan->name }}</h3>

                @if ($plan->description)
                <p class="mt-1 text-sm leading-relaxed text-muted">{{ $plan->description }}</p>
                @endif

                {{-- Price: monthly by default, yearly when the switch is on "Anual". --}}
                <div class="mt-5 min-h-24">
                    <div @class(['group-has-[#cycle-yearly:checked]/plans:hidden'=> $canYearly])>
                        <p class="text-4xl font-bold leading-none text-ink">
                            <span class="text-base font-semibold text-subtle">R$</span>
                            {{ $money($plan->price_monthly) }}
                            <span class="text-base font-medium text-subtle">/mês</span>
                        </p>
                    </div>

                    @if ($canYearly)
                    <div class="hidden group-has-[#cycle-yearly:checked]/plans:block">
                        <p class="text-4xl font-bold leading-none text-ink">
                            <span class="text-base font-semibold text-subtle">R$</span>
                            {{ $plan->yearlyMonthlyEquivalent() }}
                            <span class="text-base font-medium text-subtle">/mês</span>
                        </p>
                        <p class="mt-2 text-sm text-muted">
                            Cobrado R$ {{ $money($plan->price_yearly) }} por ano
                            @if ($discount > 0)
                            <span class="ml-1 rounded-full bg-success-soft px-2 py-0.5 text-xs font-bold text-success">-{{ $discount }}%</span>
                            @endif
                        </p>
                    </div>
                    @endif

                    @if ($plan->trial_days > 0)
                    <p class="mt-2 text-sm font-medium text-primary">{{ $plan->trial_days }} dias de teste grátis</p>
                    @endif
                </div>

                <ul class="mt-4 flex-1 space-y-2 border-t border-line pt-4 text-sm text-muted">
                    @foreach ($plan->benefits() as $benefit)
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 size-5 shrink-0 text-success')
                        {{ $benefit }}
                    </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    {{-- Monthly button: hidden while "Anual" is selected. --}}
                    <div @class(['group-has-[#cycle-yearly:checked]/plans:hidden'=> $canYearly])>
                        <x-ui.button
                            :href="route('checkout.show', ['plan' => $plan->slug, 'ciclo' => 'mensal'])"
                            :variant="$plan->is_highlighted ? 'primary' : 'tonal'"
                            size="lg"
                            class="w-full">
                            Assinar {{ $plan->name }}
                        </x-ui.button>
                    </div>

                    @if ($canYearly)
                    {{-- Yearly button: only shown while "Anual" is selected. --}}
                    <div class="hidden group-has-[#cycle-yearly:checked]/plans:block">
                        <x-ui.button
                            :href="route('checkout.show', ['plan' => $plan->slug, 'ciclo' => 'anual'])"
                            :variant="$plan->is_highlighted ? 'primary' : 'tonal'"
                            size="lg"
                            class="w-full">
                            Assinar {{ $plan->name }}
                        </x-ui.button>
                    </div>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif