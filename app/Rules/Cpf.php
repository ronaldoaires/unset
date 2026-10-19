<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid(preg_replace('/\D/', '', (string) $value))) {
            $fail('Informe um CPF válido.');
        }
    }

    /**
     * Checks length, repeated digits (111.111.111-11) and both check digits.
     */
    public static function isValid(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $cpf[$i] * (($position + 1) - $i);
            }

            if ((int) $cpf[$position] !== ((10 * $sum) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    /**
     * "12345678909" -> "123.456.789-09".
     */
    public static function format(?string $digits): string
    {
        if (blank($digits) || strlen($digits) !== 11) {
            return (string) $digits;
        }

        return sprintf('%s.%s.%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3), substr($digits, 9));
    }
}