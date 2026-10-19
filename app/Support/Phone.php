<?php

namespace App\Support;

class Phone
{
    /**
     * "66999991234" -> "(66) 99999-1234"; 10 digits -> "(66) 9999-1234".
     */
    public static function format(?string $digits): ?string
    {
        if (blank($digits)) {
            return null;
        }

        return strlen($digits) === 11
            ? sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7))
            : sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
    }
}