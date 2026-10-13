<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Models\Plan;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlansTable
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
                    ->sortable()
                    ->description(fn(Plan $record) => $record->badge_label),

                TextColumn::make('price_monthly')
                    ->label('Mensal')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('price_yearly')
                    ->label('Anual')
                    ->money('BRL')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('downloads_label')
                    ->label('Downloads')
                    ->state(fn(Plan $record) => $record->downloadsLabel())
                    ->visibleFrom('md'),

                TextColumn::make('active_subscriptions_count')
                    ->label('Assinantes')
                    ->counts('activeSubscriptions')
                    ->sortable(),

                ToggleColumn::make('is_highlighted')
                    ->label('Destaque')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn(Plan $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (Plan $record, bool $state, $livewire) {
                        // The "single highlighted plan" rule lives in Plan::booted().
                        // Re-render so the other toggles reflect the change.
                        $livewire->dispatch('$refresh');

                        Notification::make()
                            ->title('Plano ' . $record->name . ($state ? ' marcado como destaque' : ' removido dos destaques'))
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn(Plan $record) => 'Clique para ' . ($record->is_highlighted ? 'remover o destaque do' : 'destacar o') . ' plano')
                    ->visibleFrom('md'),

                ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn(Plan $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (Plan $record, bool $state) {
                        Notification::make()
                            ->title('Plano ' . $record->name . ($state ? ' ativado' : ' desativado'))
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn(Plan $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' o plano'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->modalWidth(Width::FourExtraLarge)
                    ->tooltip(fn(Plan $record) => 'Editar ' . $record->name)
                    ->successNotificationTitle(fn(Plan $record) => 'Plano ' . $record->name . ' atualizado'),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Plan $record) => 'Excluir ' . $record->name)
                    // Block deletion while any subscription (active or not) still points to this plan.
                    ->before(function (DeleteAction $action, Plan $record) {
                        if ($record->subscriptions()->exists()) {
                            Notification::make()
                                ->title('Não é possível excluir')
                                ->body('O plano ' . $record->name . ' possui assinaturas vinculadas. Desative-o em vez de excluir.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(fn(Plan $record) => 'Plano ' . $record->name . ' excluído'),
            ])
            ->defaultSort('sort_order');
    }
}
