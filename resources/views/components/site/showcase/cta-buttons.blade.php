@props([
    'service' => null,
    'project' => null,
    'inverse' => false,
    // Full-width buttons one under the other, for narrow sidebars.
    'stacked' => false,
])

@php
$params = array_filter([
    'servico' => $service?->slug,
    'projeto' => $project?->slug,
]);
@endphp

<div {{ $attributes->merge(['class' => $stacked ? 'flex flex-col gap-3 [&_a]:w-full [&_button]:w-full' : 'flex flex-col gap-3 sm:flex-row']) }}>
    <x-ui.button :href="route('quotes.create', $params)" :variant="$inverse ? 'inverse' : 'primary'" size="lg">
        @svg('heroicon-o-document-text', 'size-5')
        Solicitar orçamento
    </x-ui.button>

    {{-- A POST form (not a link): the number only exists in the server redirect,
         so crawlers that harvest phone numbers from pages never see it. --}}
    <form method="POST" action="{{ route('whatsapp') }}" target="_blank" data-no-loading class="contents">
        @csrf
        @foreach ($params as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <x-ui.button :variant="$inverse ? 'outline' : 'tonal'" size="lg">
            @svg('heroicon-o-chat-bubble-left-right', 'size-5')
            Falar no WhatsApp
        </x-ui.button>
    </form>
</div>
