<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Propaganistas\LaravelDisposableEmail\DisposableDomains;

/**
 * Blocks temporary inbox providers: the public list shipped with
 * propaganistas/laravel-disposable-email plus the project's own
 * "disposable-email.blocked" list. Subdomains count as their parent.
 */
class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email  = Str::lower((string) $value);
        $domain = Str::after($email, '@');

        $blocked = collect(config('disposable-email.blocked', []))
            ->contains(fn (string $blocked) => $domain === $blocked || str_ends_with($domain, '.' . $blocked));

        if ($blocked || app(DisposableDomains::class)->isDisposable($email)) {
            $fail('Não aceitamos e-mails temporários. Use seu e-mail pessoal ou profissional.');
        }
    }
}
