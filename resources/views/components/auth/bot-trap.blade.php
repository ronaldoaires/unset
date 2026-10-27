{{-- Bot filters for public forms. See App\Support\BotTrap. --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}">Não preencha este campo</label>
    <input type="text" id="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}" name="{{ \App\Support\BotTrap::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Support\BotTrap::TIME_FIELD }}" value="{{ \App\Support\BotTrap::token() }}">

@if (\App\Rules\Turnstile::enabled())
    <div>
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="pt-br" data-size="flexible"></div>
        @error('cf-turnstile-response')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
