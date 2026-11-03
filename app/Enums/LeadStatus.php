<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Simple sales pipeline for quote requests and contact messages.
 */
enum LeadStatus: string implements HasLabel, HasColor
{
    case New          = 'new';
    case InContact    = 'in_contact';
    case ProposalSent = 'proposal_sent';
    case Won          = 'won';
    case Lost         = 'lost';

    public function getLabel(): string
    {
        return match ($this) {
            self::New          => 'Novo',
            self::InContact    => 'Em contato',
            self::ProposalSent => 'Proposta enviada',
            self::Won          => 'Fechado',
            self::Lost         => 'Perdido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New          => 'warning',
            self::InContact    => 'info',
            self::ProposalSent => 'primary',
            self::Won          => 'success',
            self::Lost         => 'gray',
        };
    }

    /**
     * Statuses that still need some action from the admin.
     *
     * @return array<int, self>
     */
    public static function open(): array
    {
        return [self::New, self::InContact, self::ProposalSent];
    }
}
