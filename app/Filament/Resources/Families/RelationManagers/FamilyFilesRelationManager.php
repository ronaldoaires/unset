<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\FamilyFile;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Arquivos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Tipo')
                ->options([
                    FamilyFile::TYPE_RFA   => 'Arquivo Revit (.rfa)',
                    FamilyFile::TYPE_IMAGE => 'Imagem',
                    FamilyFile::TYPE_THUMB => 'Miniatura',
                ])
                ->required()
                ->live(),

            FileUpload::make('path')
                ->label('Arquivo')
                ->disk('public')
                ->directory(fn (Get $get) => 'families/' . ($get('type') ?? 'misc'))
                ->visibility('public')
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (is_object($state) && method_exists($state, 'getClientOriginalName')) {
                        $set('original_name', $state->getClientOriginalName());
                        $set('size_bytes', $state->getSize());
                        $set('mime_type', $state->getMimeType());
                    }
                }),

            Hidden::make('disk')->default('public'),
            Hidden::make('original_name'),
            Hidden::make('size_bytes'),
            Hidden::make('mime_type'),

            Toggle::make('is_primary')
                ->label('Arquivo principal deste tipo')
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        FamilyFile::TYPE_RFA   => 'RFA',
                        FamilyFile::TYPE_IMAGE => 'Imagem',
                        FamilyFile::TYPE_THUMB => 'Miniatura',
                        default                 => ucfirst($state),
                    }),

                TextColumn::make('original_name')
                    ->label('Nome do arquivo')
                    ->limit(40),

                TextColumn::make('formatted_size')
                    ->label('Tamanho'),

                IconColumn::make('is_primary')
                    ->label('Principal')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn () => ! FamilyResource::isLocked($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn () => ! FamilyResource::isLocked($this->getOwnerRecord())),
                DeleteAction::make()
                    ->authorize(fn () => ! FamilyResource::isLocked($this->getOwnerRecord())),
            ]);
    }
}