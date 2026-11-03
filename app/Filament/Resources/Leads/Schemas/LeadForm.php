<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

/**
 * Only the pipeline fields are editable: what the client sent stays as sent.
 */
class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label('Status')
                ->options(LeadStatus::class)
                ->required()
                ->native(false),

            Textarea::make('notes')
                ->label('Anotações internas')
                ->helperText('Só você vê. Ex: valor proposto, data do retorno, próximos passos.')
                ->rows(5)
                ->maxLength(5000),
        ]);
    }
}
