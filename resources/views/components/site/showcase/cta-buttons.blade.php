@props(['service' => null, 'project' => null, 'inverse' => false])

@php
// The quote form and the WhatsApp redirect arrive in the next step; until
// their routes exist the buttons are simply not rendered.
$params = array_filter([
    'servico' => $service?->slug,
    'projeto' => $project?->slug,
]);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row']) }}>
    @if (Route::has('quotes.create'))
    <x-ui.button :href="route('quotes.create', $params)" :variant="$inverse ? 'inverse' : 'primary'" size="lg">
        @svg('heroicon-o-document-text', 'size-5')
        Solicitar orçamento
    </x-ui.button>
    @endif

    {{-- Goes through our own redirect so the phone number never appears in the HTML. --}}
    @if (Route::has('whatsapp'))
    <x-ui.button
        :href="route('whatsapp', $params)"
        :variant="$inverse ? 'outline' : 'tonal'"
        size="lg"
        rel="nofollow"
        target="_blank">
        @svg('heroicon-o-chat-bubble-left-right', 'size-5')
        Falar no WhatsApp
    </x-ui.button>
    @endif
</div>
