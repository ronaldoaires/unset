<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a lead came from: the quote request form on the service pages
 * or the general "Fale conosco" form in the footer.
 */
enum LeadType: string implements HasLabel, HasColor
{
    case Quote   = 'quote';
    case Contact = 'contact';

    public function getLabel(): string
    {
        return match ($this) {
            self::Quote   => 'Orçamento',
            self::Contact => 'Fale conosco',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Quote   => 'primary',
            self::Contact => 'gray',
        };
    }
}
