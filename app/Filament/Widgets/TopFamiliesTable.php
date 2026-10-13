<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\Family;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopFamiliesTable extends TableWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Famílias mais acessadas')
            ->description('Clique no título de uma coluna para ordenar por visualizações, downloads ou favoritos.')
            ->query(fn () => Family::published()
                ->with('category')
                ->withCount('favorites'))
            ->columns([
                TextColumn::make('name')
                    ->label('Família')
                    ->searchable()
                    ->description(fn (Family $record) => $record->category?->name),

                TextColumn::make('view_count')
                    ->label('Visualizações')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('favorites_count')
                    ->label('Favoritos')
                    ->numeric()
                    ->sortable(),
            ])
            ->recordUrl(fn (Family $record) => FamilyResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('download_count', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}