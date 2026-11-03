<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks what spam messages look like: HTML or BBCode links and piles of
 * URLs. A real client may paste one or two links (their current site, a
 * reference), so plain links are allowed up to a small limit.
 */
class NoSpamContent implements ValidationRule
{
    public function __construct(private readonly int $maxLinks = 2) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (preg_match('/<\s*a\s|\[\s*url|\[\s*link|<\s*script/i', $value)) {
            $fail('Escreva a mensagem em texto simples, sem códigos ou HTML.');

            return;
        }

        $links = preg_match_all('#(https?://|www\.)\S+#i', $value);

        if ($links > $this->maxLinks) {
            $fail('A mensagem tem links demais. Envie no máximo ' . $this->maxLinks . '.');
        }
    }
}
