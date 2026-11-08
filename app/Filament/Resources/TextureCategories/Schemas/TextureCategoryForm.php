<?php

namespace App\Filament\Resources\TextureCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TextureCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100),

                TextInput::make('sort_order')
                    ->label('Posição')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Menor aparece primeiro.'),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true)
                    ->inline(false),
            ]);
    }
}
