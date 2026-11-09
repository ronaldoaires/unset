@props(['compact' => false])

{{-- "Use it in Revit" highlight: the plugin makes a native material at real size in a few clicks. --}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-card bg-gradient-to-br from-brand-700 to-brand-900 text-white shadow-card']) }}>
    <div class="grid gap-6 p-6 sm:p-8 {{ $compact ? '' : 'md:grid-cols-5 md:items-center' }}">
        <div class="{{ $compact ? '' : 'md:col-span-3' }}">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-200">Plugin UnSet BIM para Revit · grátis</p>
            <h2 class="mt-2 text-2xl font-bold leading-tight">Textura no Revit em 3 cliques, já no tamanho real</h2>
            <p class="mt-2 leading-relaxed text-brand-100">
                Sem baixar arquivo, sem montar material, sem acertar escala na mão. O plugin cria o material nativo do Revit
                com cor, relevo e rugosidade, e você pinta as faces direto no modelo.
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-ui.button :href="route('plugin.download')" variant="inverse">
                    @svg('heroicon-o-arrow-down-tray', 'size-5')
                    Baixar o plugin grátis
                </x-ui.button>
            </div>
            <p class="mt-2 text-xs text-brand-200">Revit 2025 ou superior · Windows</p>
        </div>

        @unless ($compact)
            <ol class="space-y-3 md:col-span-2">
                @foreach ([
                    ['Abra o painel', 'Aba UnSet BIM › Texturas, ao lado de Propriedades.'],
                    ['Escolha a textura', 'Busque por categoria, tipo ou nome.'],
                    ['Pinte as faces', 'Clique nas paredes, pisos e forros. Pronto.'],
                ] as $i => [$step, $text])
                    <li class="flex gap-3 rounded-control bg-white/10 p-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-brand-700">{{ $i + 1 }}</span>
                        <span>
                            <span class="block font-semibold">{{ $step }}</span>
                            <span class="block text-sm text-brand-100">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        @endunless
    </div>
</section>
