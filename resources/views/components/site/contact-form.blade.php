@props(['id' => 'fale-conosco'])

{{-- "Fale conosco". Fields are prefixed with "contact_" and errors use the
     "contact" bag (see ContactController), so this form never mixes with
     another form on the same page, like the quote form. --}}
@php
$bag = $errors->getBag(\App\Http\Controllers\Site\Showcase\ContactController::BAG);
$field = 'block w-full rounded-control border bg-surface px-3.5 py-2 text-base text-ink shadow-sm placeholder:text-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
$border = fn (string $name) => $bag->has($name) ? 'border-red-500' : 'border-line';
@endphp

<form id="{{ $id }}" method="POST" action="{{ route('contact.store') }}" {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @csrf
    <x-auth.bot-trap lazy :bag="\App\Http\Controllers\Site\Showcase\ContactController::BAG" />

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $id }}-name" class="mb-1.5 block text-sm font-medium text-ink">Nome</label>
            <input id="{{ $id }}-name" name="contact_name" type="text" value="{{ old('contact_name') }}" autocomplete="name" maxlength="100" required class="{{ $field }} min-h-12 {{ $border('contact_name') }}">
            @if ($bag->has('contact_name'))<p class="mt-1.5 text-sm text-red-600">{{ $bag->first('contact_name') }}</p>@endif
        </div>

        <div>
            <label for="{{ $id }}-email" class="mb-1.5 block text-sm font-medium text-ink">E-mail</label>
            <input id="{{ $id }}-email" name="contact_email" type="email" value="{{ old('contact_email') }}" autocomplete="email" maxlength="255" required class="{{ $field }} min-h-12 {{ $border('contact_email') }}">
            @if ($bag->has('contact_email'))<p class="mt-1.5 text-sm text-red-600">{{ $bag->first('contact_email') }}</p>@endif
        </div>
    </div>

    <div>
        <label for="{{ $id }}-message" class="mb-1.5 block text-sm font-medium text-ink">Mensagem</label>
        <textarea id="{{ $id }}-message" name="contact_message" rows="4" minlength="10" maxlength="3000" required class="{{ $field }} {{ $border('contact_message') }}">{{ old('contact_message') }}</textarea>
        @if ($bag->has('contact_message'))<p class="mt-1.5 text-sm text-red-600">{{ $bag->first('contact_message') }}</p>@endif
    </div>

    <div>
        <label class="flex items-start gap-2.5 text-sm text-muted">
            <input type="checkbox" name="contact_privacy" value="1" @checked(old('contact_privacy')) required class="mt-0.5 size-5 shrink-0 rounded border-line text-primary focus:ring-primary">
            <span>Li e aceito a <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-primary underline">Política de Privacidade</a>.</span>
        </label>
        @if ($bag->has('contact_privacy'))<p class="mt-1.5 text-sm text-red-600">{{ $bag->first('contact_privacy') }}</p>@endif
    </div>

    <x-ui.button class="w-full sm:w-auto">
        @svg('heroicon-o-paper-airplane', 'size-5')
        Enviar mensagem
    </x-ui.button>
</form>

{{-- Came back with errors: load the challenge right away (see app.js). --}}
@if ($bag->any())
<script>document.querySelector('#{{ $id }} [data-turnstile-lazy]')?.setAttribute('data-autoload', '');</script>
@endif
