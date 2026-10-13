<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('icon')
                    ->label('Ícone')
                    ->maxLength(255)
                    ->helperText('Nome do ícone usado no site público (ex: door, window, stair)'),

                TextInput::make('sort_order')
                    ->label('Ordem de exibição')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true)
                    ->inline(false),

                Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }
}
