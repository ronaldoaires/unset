<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BrandForm
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

                TextInput::make('website')
                    ->label('Site')
                    ->url()
                    ->maxLength(150),

                // Kept as a raw upload (storeFiles(false)) so the Create/Edit
                // pages can run it through ImageService (webp + thumbnail).
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(2048)
                    ->disk('public')
                    ->visibility('public')
                    ->storeFiles(false)
                    ->helperText('PNG, JPG ou WebP, até 2 MB.'),

                TextInput::make('country')
                    ->label('País (ISO)')
                    ->default('BR')
                    ->maxLength(2)
                    ->minLength(2)
                    ->helperText('Código ISO de 2 letras: BR, US, DE...')
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn(?string $state) => filled($state) ? strtoupper($state) : null),

                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true)
                    ->inline(false),
            ]);
    }
}
