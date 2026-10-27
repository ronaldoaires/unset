<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Cheap bot filters for public forms, rendered by <x-auth.bot-trap />:
 *
 * - honeypot: a field hidden from people; bots fill every field they find;
 * - time trap: the form carries its (encrypted) render time, and bots
 *   submit within a second or two, while people take longer.
 */
class BotTrap
{
    public const HONEYPOT_FIELD = 'hp_extra_info';
    public const TIME_FIELD     = 'form_token';

    /** Faster than this is not a person filling the form. */
    public const MIN_SECONDS = 3;

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
