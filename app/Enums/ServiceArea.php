<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The two fixed areas the services are grouped in. Kept as an enum
 * (not a table) because they are part of the site structure: each one
 * has its own public page under /servicos/{slug}.
 */
enum ServiceArea: string implements HasLabel
{
    case Engineering = 'engineering';
    case Technology  = 'technology';

    public function getLabel(): string
    {
        return match ($this) {
            self::Engineering => 'Engenharia',
            self::Technology  => 'Tecnologia',
        };
    }

    /**
     * Portuguese URL segment used by the public routes.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Engineering => 'engenharia',
            self::Technology  => 'tecnologia',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }
}
