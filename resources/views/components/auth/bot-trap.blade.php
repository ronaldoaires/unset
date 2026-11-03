@props([
    // Lazy: Turnstile loads only when the visitor starts using this form
    // (see resources/js/app.js). Used by the footer form, which is on every
    // page, so the challenge script doesn't weigh on every page view.
    'lazy' => false,
    // Error bag of the form, for pages with more than one form.
    'bag'  => 'default',
])

{{-- Bot filters for public forms. See App\Support\BotTrap. --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}-{{ $bag }}">Não preencha este campo</label>
    <input type="text" id="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}-{{ $bag }}" name="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Support\BotTrap::TIME_FIELD }}" value="{{ \App\Support\BotTrap::token() }}">

@if (\App\Rules\Turnstile::enabled())
    <div>
        @if ($lazy)
            <div data-turnstile-lazy data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
        @else
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="pt-br" data-size="flexible"></div>
            @once
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endonce
        @endif

        @if ($errors->getBag($bag)->has('cf-turnstile-response'))
            <p class="mt-1.5 text-sm text-red-600">{{ $errors->getBag($bag)->first('cf-turnstile-response') }}</p>
        @endif
    </div>
@endif
