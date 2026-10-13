<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\Family;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingFamiliesTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Famílias aguardando revisão')
            ->query(fn () => Family::pending()->with(['user', 'category']))
            ->columns([
                TextColumn::make('name')
                    ->label('Família')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Enviado por'),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Enviada')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Revisar')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Family $record) => FamilyResource::getUrl('edit', ['record' => $record])),
            ])
            // Oldest submissions first: the queue should be worked in order.
            ->defaultSort('created_at', 'asc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nenhuma família aguardando revisão');
    }
}