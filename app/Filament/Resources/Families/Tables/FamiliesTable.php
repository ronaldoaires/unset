<?php

namespace App\Filament\Resources\Families\Tables;

use App\Models\Category;
use App\Models\Family;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['thumbnail', 'primaryImage', 'brand']))
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder-family.png')),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Family $record) => $record->brand?->name),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        Family::STATUS_DRAFT          => 'Rascunho',
                        Family::STATUS_PENDING_REVIEW => 'Aguardando revisão',
                        Family::STATUS_PUBLISHED      => 'Publicado',
                        Family::STATUS_REJECTED       => 'Rejeitado',
                        default                        => ucfirst($state),
                    })
                    ->color(fn(string $state) => match ($state) {
                        Family::STATUS_DRAFT          => 'gray',
                        Family::STATUS_PENDING_REVIEW => 'warning',
                        Family::STATUS_PUBLISHED      => 'success',
                        Family::STATUS_REJECTED       => 'danger',
                        default                        => 'gray',
                    }),

                IconColumn::make('is_free')
                    ->label('Gratuita')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('formatted_price')
                    ->label('Preço'),

                IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Enviado por')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Family::STATUS_DRAFT          => 'Rascunho',
                        Family::STATUS_PENDING_REVIEW => 'Aguardando revisão',
                        Family::STATUS_PUBLISHED      => 'Publicado',
                        Family::STATUS_REJECTED       => 'Rejeitado',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->options(fn() => Category::query()->pluck('name', 'id')),

                TernaryFilter::make('is_free')
                    ->label('Gratuita'),

                TernaryFilter::make('is_featured')
                    ->label('Destaque'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('approve')
                        ->label('Aprovar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->authorize('approve')
                        ->visible(fn(Family $record) => $record->isPending() || $record->isRejected())
                        ->requiresConfirmation()
                        ->action(function (Family $record) {
                            $record->update([
                                'status'           => Family::STATUS_PUBLISHED,
                                'approved_by'      => auth()->id(),
                                'approved_at'      => now(),
                                'rejection_reason' => null,
                            ]);
                        }),

                    Action::make('reject')
                        ->label('Rejeitar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->authorize('reject')
                        ->visible(fn(Family $record) => $record->status !== Family::STATUS_REJECTED)
                        ->schema([
                            Textarea::make('rejection_reason')
                                ->label('Motivo da rejeição')
                                ->required(),
                        ])
                        ->action(function (Family $record, array $data) {
                            $record->update([
                                'status'           => Family::STATUS_REJECTED,
                                'approved_by'      => auth()->id(),
                                'approved_at'      => now(),
                                'rejection_reason' => $data['rejection_reason'],
                            ]);
                        }),

                    DeleteAction::make()
                        ->failureNotificationTitle(
                            fn(Family $record) => 'Não foi possível excluir ' . $record->name . ': a família possui compras registradas'
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->failureNotificationTitle('Algumas famílias não foram excluídas: possuem compras registradas'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
