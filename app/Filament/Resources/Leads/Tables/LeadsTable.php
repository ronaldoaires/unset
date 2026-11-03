<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recebido')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(['name', 'email', 'phone'])
                    ->description(fn (Lead $record) => $record->email),

                TextColumn::make('service.name')
                    ->label('Serviço')
                    ->placeholder('—')
                    ->visibleFrom('md'),

                TextColumn::make('city')
                    ->label('Cidade')
                    ->state(fn (Lead $record) => collect([$record->city, $record->state])->filter()->join('/') ?: null)
                    ->placeholder('—')
                    ->visibleFrom('lg'),

                SelectColumn::make('status')
                    ->label('Status')
                    ->options(LeadStatus::class)
                    ->selectablePlaceholder(false)
                    // The select writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn (Lead $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(fn (Lead $record) => Notification::make()
                        ->title('Contato de ' . $record->name . ': ' . $record->status->getLabel())
                        ->success()
                        ->send()),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(LeadType::class),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(LeadStatus::class),

                SelectFilter::make('service_id')
                    ->label('Serviço')
                    ->relationship('service', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Lead $record) => 'Ver pedido de ' . $record->name),

                Action::make('whatsapp')
                    ->iconButton()
                    ->size(Size::Large)
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->tooltip(fn (Lead $record) => 'Chamar ' . $record->name . ' no WhatsApp')
                    ->url(fn (Lead $record) => $record->whatsappUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Lead $record) => filled($record->phone)),

                Action::make('email')
                    ->iconButton()
                    ->size(Size::Large)
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->tooltip(fn (Lead $record) => 'Responder ' . $record->name . ' por e-mail')
                    ->url(fn (Lead $record) => 'mailto:' . $record->email . '?subject=' . rawurlencode(
                        $record->service ? 'Orçamento: ' . $record->service->name : 'Seu contato com a UnSet'
                    )),

                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip(fn (Lead $record) => 'Status e anotações de ' . $record->name)
                    ->modalHeading(fn (Lead $record) => 'Contato de ' . $record->name)
                    ->successNotificationTitle(fn (Lead $record) => 'Contato de ' . $record->name . ' atualizado'),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Lead $record) => 'Excluir contato de ' . $record->name)
                    ->modalDescription('Os dados do cliente serão apagados de vez. Use quando ele pedir a exclusão (LGPD) ou for spam.')
                    ->successNotificationTitle(fn (Lead $record) => 'Contato de ' . $record->name . ' excluído'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
