<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_thumb_url')
                    ->label('')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area')
                    ->label('Área')
                    ->badge()
                    ->sortable(),

                TextColumn::make('projects_count')
                    ->label('Projetos')
                    ->counts('projects')
                    ->sortable(),

                TextColumn::make('view_count')
                    ->label('Visitas')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_viewed_at')
                    ->label('Última visita')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('sort_order')
                    ->label('Posição')
                    ->sortable()
                    ->visibleFrom('lg'),

                ToggleColumn::make('is_featured')
                    ->label('Destaque')
                    ->disabled(fn (Service $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(fn (Service $record, bool $state) => Notification::make()
                        ->title('Serviço ' . $record->name . ($state ? ' em destaque' : ' fora do destaque'))
                        ->success()
                        ->send())
                    ->tooltip(fn (Service $record) => 'Clique para ' . ($record->is_featured ? 'tirar do' : 'colocar em') . ' destaque'),

                ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn (Service $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(fn (Service $record, bool $state) => Notification::make()
                        ->title('Serviço ' . $record->name . ($state ? ' ativado' : ' desativado'))
                        ->success()
                        ->send())
                    ->tooltip(fn (Service $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' o serviço'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Ativo'),
                TernaryFilter::make('is_featured')->label('Destaque'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Service $record) => 'Editar ' . $record->name),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Service $record) => 'Excluir ' . $record->name)
                    // The projects FK is restrictive: move or delete the portfolio first.
                    ->hidden(fn (Service $record) => $record->projects_count > 0)
                    ->successNotificationTitle(fn (Service $record) => 'Serviço ' . $record->name . ' excluído'),
            ])
            ->defaultSort('sort_order');
    }
}
