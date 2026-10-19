<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    protected static function statusOptions(): array
    {
        return [
            'active'   => 'Ativa',
            'trial'    => 'Trial',
            'canceled' => 'Cancelada',
            'past_due' => 'Pagamento pendente',
            'expired'  => 'Expirada',
            'pending' => 'Aguardando pagamento'
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Subscription $record) => $record->user?->email),

                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'yearly' ? 'Anual' : 'Mensal')
                    ->color(fn (string $state) => $state === 'yearly' ? 'info' : 'gray')
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Subscription $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        'active'   => 'success',
                        'trial'    => 'info',
                        'canceled' => 'danger',
                        'past_due' => 'warning',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('renews_at')
                    ->label('Renovação')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('last_payment_amount')
                    ->label('Último pagamento')
                    ->money('BRL')
                    ->placeholder('—')
                    ->description(fn (Subscription $record) => $record->last_payment_at?->format('d/m/Y'))
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('canceled_at')
                    ->label('Cancelada em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mp_preapproval_id')
                    ->label('ID Mercado Pago')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => static::statusOptions()),

                SelectFilter::make('plan_id')
                    ->label('Plano')
                    ->options(fn () => Plan::query()->ordered()->pluck('name', 'id')),

                SelectFilter::make('billing_cycle')
                    ->label('Ciclo')
                    ->options([
                        'monthly' => 'Mensal',
                        'yearly'  => 'Anual',
                    ]),
            ])
            // Read-only: the only record action is the link to the payments page.
            ->recordActions([
                Action::make('payments')
                    ->icon('heroicon-o-banknotes')
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (Subscription $record) => 'Pagamentos de ' . $record->user?->name)
                    ->url(fn (Subscription $record) => SubscriptionResource::getUrl('payments', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}