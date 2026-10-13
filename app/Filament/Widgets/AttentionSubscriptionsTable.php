<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AttentionSubscriptionsTable extends TableWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assinaturas que precisam de atenção')
            ->description('Pagamento pendente ou expirando nos próximos 7 dias.')
            ->query(fn () => Subscription::query()
                ->with(['user', 'plan'])
                ->where(function (Builder $query) {
                    $query->where('status', 'past_due')
                        ->orWhere(function (Builder $query) {
                            $query->whereIn('status', ['active', 'trial'])
                                ->whereBetween('expires_at', [now(), now()->addDays(7)]);
                        });
                }))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->description(fn (Subscription $record) => $record->user?->email),

                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Subscription $record) => $record->statusLabel())
                    ->color(fn (string $state) => $state === 'past_due' ? 'warning' : 'info'),

                TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('payments')
                    ->label('Pagamentos')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn (Subscription $record) => SubscriptionResource::getUrl('payments', ['record' => $record])),
            ])
            ->defaultSort('expires_at', 'asc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nenhuma assinatura precisa de atenção');
    }
}