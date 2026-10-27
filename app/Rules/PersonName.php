<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A person's name: letters of any alphabet (accents included), spaces,
 * apostrophes, hyphens and dots. Blocks links, HTML, digits and symbols,
 * which is what spam bots put in the name field.
 *
 * Normalize spaces before validating (see PersonName::normalize()).
 */
class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        $valid = preg_match("/^\\p{L}[\\p{L}\\p{M}'’.\\- ]*$/u", $value) === 1
            // At least two letters: "A." or "-" alone is not a name.
            && preg_match_all('/\p{L}/u', $value) >= 2
            // No letter repeated four times in a row ("ххххх", "aaaa").
            && preg_match('/(\p{L})\1{3,}/u', mb_strtolower($value)) === 0;

        if (! $valid) {
            $fail('Informe seu nome usando apenas letras, espaços, apóstrofo, hífen ou ponto.');
        }
    }

    /**
     * Trims and collapses repeated spaces: "  Ana   Maria " → "Ana Maria".
     */
    public static function normalize(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }
}
