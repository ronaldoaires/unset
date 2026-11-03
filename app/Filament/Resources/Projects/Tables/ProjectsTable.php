<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_thumb_url')
                    ->label('')
                    ->square(),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('service.name')
                    ->label('Serviço')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('images_count')
                    ->label('Fotos')
                    ->counts('images')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Local')
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('view_count')
                    ->label('Visitas')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_viewed_at')
                    ->label('Última visita')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),

                ToggleColumn::make('is_featured')
                    ->label('Destaque')
                    ->disabled(fn (Project $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(fn (Project $record, bool $state) => Notification::make()
                        ->title('Projeto ' . $record->title . ($state ? ' em destaque' : ' fora do destaque'))
                        ->success()
                        ->send())
                    ->tooltip(fn (Project $record) => 'Clique para ' . ($record->is_featured ? 'tirar do' : 'colocar em') . ' destaque'),

                ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn (Project $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(fn (Project $record, bool $state) => Notification::make()
                        ->title('Projeto ' . $record->title . ($state ? ' ativado' : ' desativado'))
                        ->success()
                        ->send())
                    ->tooltip(fn (Project $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' o projeto'),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label('Serviço')
                    ->relationship('service', 'name'),

                TernaryFilter::make('is_active')->label('Ativo'),
                TernaryFilter::make('is_featured')->label('Destaque'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Project $record) => 'Editar ' . $record->title),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Project $record) => 'Excluir ' . $record->title)
                    ->successNotificationTitle(fn (Project $record) => 'Projeto ' . $record->title . ' excluído'),
            ])
            ->defaultSort('sort_order');
    }
}
