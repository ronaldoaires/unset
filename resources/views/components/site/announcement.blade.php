{{-- Launch-phase bar above the header (config site.announcement.enabled). --}}
@if (config('site.announcement.enabled'))
<div class="bg-brand-900 text-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-2 px-4 py-2.5 text-center text-sm sm:flex-row sm:justify-center sm:gap-4 sm:text-left">
        <p class="leading-snug">
            <span class="mr-1 inline-flex items-center rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-950">Novo</span>
            {{-- Short text on phones, full text from the sm breakpoint up. --}}
            <span class="sm:hidden">Conteúdo novo e gratuito todos os dias. Baixe famílias e texturas pelo site ou direto no Revit.</span>
            <span class="hidden sm:inline">
                <strong class="font-semibold">Portal em lançamento:</strong>
                famílias e texturas novas e gratuitas todos os dias. Baixe pelo site ou direto no Revit com o plugin UnSet BIM, que também é grátis.
                Seja um dos primeiros: estamos construindo as melhores ferramentas BIM do Brasil.
            </span>
        </p>
        <a
            href="{{ route('plugin.download') }}"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-700 transition hover:bg-brand-50">
            @svg('heroicon-o-arrow-down-tray', 'size-4')
            Baixar plugin grátis
        </a>
    </div>
</div>
@endif
