<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile check (free, no puzzle for real people).
 * Only active when TURNSTILE_SECRET_KEY is set; see Turnstile::enabled().
 */
class Turnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $passed = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret'   => config('services.turnstile.secret_key'),
                    'response' => (string) $value,
                    'remoteip' => request()->ip(),
                ])
                ->json('success') === true;
        } catch (ConnectionException) {
            $passed = false;
        }

        if (! $passed) {
            $fail('Não conseguimos confirmar que você não é um robô. Tente novamente.');
        }
    }
}
