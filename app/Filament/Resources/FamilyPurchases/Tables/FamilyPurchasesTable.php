<?php

namespace App\Filament\Resources\FamilyPurchases\Tables;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\FamilyPurchase;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class FamilyPurchasesTable
{
    protected static function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid'      => 'Pago',
            'pending'   => 'Pendente',
            'refunded'  => 'Estornado',
            'failed'    => 'Falhou',
            'cancelled' => 'Cancelado',
            default     => ucfirst((string) $status),
        };
    }

    protected static function methodLabel(?string $method): string
    {
        return match ($method) {
            'credit_card' => 'Cartão de crédito',
            'debit_card'  => 'Cartão de débito',
            'pix'         => 'Pix',
            default       => $method ?? '—',
        };
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Não pago')
                    ->description(fn (FamilyPurchase $record) => 'Registrado em ' . $record->created_at?->format('d/m/Y'))
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Comprador')
                    ->searchable()
                    ->sortable()
                    ->description(fn (FamilyPurchase $record) => $record->user?->email),

                TextColumn::make('family.name')
                    ->label('Família')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Família removida')
                    ->visibleFrom('md'),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    // Footer total counts only paid purchases and follows the active filters.
                    ->summarize(
                        Sum::make()
                            ->label('Total pago')
                            ->money('BRL')
                            ->query(fn (Builder $query) => $query->where('status', 'paid'))
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::statusLabel($state))
                    ->color(fn (?string $state) => match ($state) {
                        'paid'      => 'success',
                        'pending'   => 'warning',
                        'refunded'  => 'info',
                        'failed'    => 'danger',
                        default     => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Forma de pagamento')
                    ->formatStateUsing(fn (?string $state) => static::methodLabel($state))
                    ->visibleFrom('md'),

                TextColumn::make('payment_id')
                    ->label('ID do pagamento')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Options come from the data itself, so no status name is guessed.
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => FamilyPurchase::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->pluck('status')
                        ->mapWithKeys(fn (string $status) => [$status => static::statusLabel($status)])
                        ->all()),

                SelectFilter::make('payment_method')
                    ->label('Forma de pagamento')
                    ->options(fn () => FamilyPurchase::query()
                        ->whereNotNull('payment_method')
                        ->distinct()
                        ->pluck('payment_method')
                        ->mapWithKeys(fn (string $method) => [$method => static::methodLabel($method)])
                        ->all()),

                SelectFilter::make('family_id')
                    ->label('Família')
                    ->relationship('family', 'name')
                    ->searchable()
                    ->preload(),
            ])
            // Read-only: the only record action is a shortcut to the purchased family.
            ->recordActions([
                Action::make('family')
                    ->icon('heroicon-o-rectangle-stack')
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn (FamilyPurchase $record) => 'Abrir ' . $record->family?->name)
                    ->visible(fn (FamilyPurchase $record) => $record->family !== null)
                    ->url(fn (FamilyPurchase $record) => FamilyResource::getUrl('edit', ['record' => $record->family])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}