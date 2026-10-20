@php
$price = number_format((float) $family->price, 2, ',', '.');
@endphp

<x-layouts.site :title="'Comprar ' . $family->name" :noindex="true">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Finalizar compra</h1>
        <p class="mt-1 text-sm text-muted">Confira a família e complete seus dados para seguir ao pagamento.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">

            {{-- Data --}}
            <form
                method="POST"
                action="{{ route('revit.families.purchase.store', ['family' => $family->slug]) }}"
                class="space-y-6 rounded-card bg-surface p-6 shadow-card lg:col-span-3">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-ink">Seus dados</h2>
                    <p class="mt-1 text-sm text-muted">Pedimos só o necessário para identificar seu pagamento.</p>
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
                    hint="Usado apenas para verificar sua conta."
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
                <p class="text-sm text-subtle">Família escolhida</p>
                <h2 class="mt-0.5 text-xl font-bold text-ink">{{ $family->name }}</h2>

                @if ($family->image_url)
                <img
                    src="{{ $family->image_url }}"
                    alt="{{ $family->name }} - família Revit"
                    class="mt-4 aspect-[4/3] w-full rounded-control bg-canvas object-contain">
                @endif

                <div class="mt-4 border-t border-line pt-4">
                    <p class="text-3xl font-bold leading-none text-ink">
                        <span class="text-base font-semibold text-subtle">R$</span>
                        {{ $price }}
                    </p>
                    <p class="mt-2 text-sm text-muted">Pagamento único. Você escolhe Pix ou cartão no ambiente seguro do Mercado Pago.</p>
                </div>

                <ul class="mt-4 space-y-2 border-t border-line pt-4 text-sm text-muted">
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 size-5 shrink-0 text-success')
                        Fica liberada na sua conta para novos downloads
                    </li>
                    <li class="flex items-start gap-2">
                        @svg('heroicon-o-check-circle', 'mt-0.5 size-5 shrink-0 text-success')
                        Não consome o limite mensal do seu plano
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</x-layouts.site>