<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ToggleColumn;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subcategories_count')
                    ->label('Subcategorias')
                    ->counts('subcategories')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('families_count')
                    ->label('Famílias')
                    ->counts('families')
                    ->sortable()
                    ->visibleFrom('md'),

                    TextColumn::make('view_count')
    ->label('Views')
    ->numeric()
    ->sortable(),

TextColumn::make('last_viewed_at')
    ->label('Última visita')
    ->since()
    ->dateTimeTooltip('d/m/Y H:i')
    ->placeholder('Nunca')
    ->sortable()
    ->visibleFrom('md'),

                ToggleColumn::make('is_active')
                    ->label('Ativa')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn(Category $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (Category $record, bool $state) {
                        Notification::make()
                            ->title('Categoria ' . $record->name . ($state ? ' ativada' : ' desativada'))
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn(Category $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' a categoria'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Ativa'),
            ])
            ->recordActions([
                Action::make('subcategories')
                    ->icon('heroicon-o-queue-list')
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Category $record) => 'Subcategorias de ' . $record->name)
                    ->url(fn(Category $record) => CategoryResource::getUrl('subcategories', ['record' => $record])),

                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Category $record) => 'Editar ' . $record->name)
                    ->successNotificationTitle(fn(Category $record) => 'Categoria ' . $record->name . ' atualizada'),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Category $record) => 'Excluir ' . $record->name)
                    // Block deletion while families still point to this category.
                    ->before(function (DeleteAction $action, Category $record) {
                        if ($record->families()->exists()) {
                            Notification::make()
                                ->title('Não é possível excluir')
                                ->body('A categoria ' . $record->name . ' possui famílias vinculadas.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(fn(Category $record) => 'Categoria ' . $record->name . ' excluída'),
            ])
            ->defaultSort('sort_order');
    }
}
