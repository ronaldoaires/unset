<?php

namespace App\Filament\Resources\TextureCategories\Tables;

use App\Models\TextureCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TextureCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('textures_count')
                    ->label('Texturas')
                    ->counts('textures')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Posição')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Ativa')
                    // The toggle writes straight to the record, so the policy is checked by hand.
                    ->disabled(fn(TextureCategory $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (TextureCategory $record, bool $state) {
                        Notification::make()
                            ->title('Categoria ' . $record->name . ($state ? ' ativada' : ' desativada'))
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Ativa'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(TextureCategory $record) => 'Editar ' . $record->name),

                // The FK restricts the delete while textures use the category.
                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(TextureCategory $record) => 'Excluir ' . $record->name)
                    ->disabled(fn(TextureCategory $record) => $record->textures()->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
