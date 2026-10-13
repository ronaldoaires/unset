<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\SubscriptionPayment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageSubscriptionPayments extends ManageRelatedRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected static string $relationship = 'payments';

    public function getTitle(): string|Htmlable
    {
        return 'Pagamentos de ' . $this->getRecord()->user?->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SubscriptionResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('pagamento')
            ->pluralModelLabel('pagamentos')
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Não pago')
                    ->description(fn (SubscriptionPayment $record) => 'Registrado em ' . $record->created_at?->format('d/m/Y'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionPayment $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'pending'  => 'warning',
                        'rejected' => 'danger',
                        'refunded' => 'info',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Forma de pagamento')
                    ->formatStateUsing(fn (SubscriptionPayment $record) => $record->paymentMethodLabel())
                    ->visibleFrom('md'),

                TextColumn::make('mp_payment_id')
                    ->label('ID Mercado Pago')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'approved'  => 'Aprovado',
                        'pending'   => 'Pendente',
                        'rejected'  => 'Rejeitado',
                        'refunded'  => 'Estornado',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            // Read-only payment history: no header, record or bulk actions.
            ->defaultSort('created_at', 'desc');
    }
}