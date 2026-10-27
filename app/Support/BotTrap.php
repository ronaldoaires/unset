<?php

namespace App\Support;

use App\Rules\Turnstile;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Cheap bot filters for public forms, rendered by <x-auth.bot-trap />:
 *
 * - honeypot: a field hidden from people; bots fill every field they find;
 * - time trap: the form carries its (encrypted) render time, and bots
 *   submit within a second or two, while people take longer;
 * - Cloudflare Turnstile, when its keys are configured.
 */
class BotTrap
{
    public const HONEYPOT_FIELD = 'hp_extra_info';
    public const TIME_FIELD     = 'form_token';

    /** Faster than this is not a person filling the form. */
    public const MIN_SECONDS = 3;

    /**
     * Runs every filter, before the form's own validation.
     *
     * Returns false when the honeypot was filled: the caller answers exactly as
     * on success but does nothing, so the bot learns nothing. Throws a
     * validation error (under $errorKey) when the form came back too fast, and
     * under "cf-turnstile-response" when Turnstile refuses.
     */
    public static function verify(Request $request, string $errorKey = 'email'): bool
    {
        if (self::honeypotFilled($request)) {
            return false;
        }

        // A real person just sends the form again.
        if (! self::slowEnough($request)) {
            throw ValidationException::withMessages([
                $errorKey => 'Não foi possível concluir o envio. Confira os dados e envie novamente.',
            ]);
        }

        Validator::make($request->all(), [
            'cf-turnstile-response' => [Rule::requiredIf(Turnstile::enabled()), new Turnstile()],
        ], [
            'cf-turnstile-response.required' => 'Confirme que você não é um robô.',
        ])->validate();

        return true;
    }

    public static function token(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    public static function honeypotFilled(Request $request): bool
    {
        return filled($request->input(self::HONEYPOT_FIELD));
    }

    /**
     * False when the render time is missing, tampered with, or too recent.
     */
    public static function slowEnough(Request $request): bool
    {
        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input(self::TIME_FIELD));
        } catch (DecryptException) {
            return false;
        }

        return now()->getTimestamp() - $renderedAt >= self::MIN_SECONDS;
    }
}
