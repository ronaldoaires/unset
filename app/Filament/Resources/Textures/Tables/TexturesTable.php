<?php

namespace App\Filament\Resources\Textures\Tables;

use App\Models\Texture;
use App\Models\TextureCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TexturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['category', 'user'])->withCount('maps'))
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Texture $record) => $record->tile_size_label),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('material_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn(string $state) => Texture::MATERIAL_TYPES[$state] ?? $state)
                    ->toggleable(),

                TextColumn::make('maps_count')
                    ->label('Mapas')
                    ->sortable(),

                ColorColumn::make('shading_color')
                    ->label('Cor')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        Texture::STATUS_DRAFT          => 'Rascunho',
                        Texture::STATUS_PENDING_REVIEW => 'Aguardando revisão',
                        Texture::STATUS_PUBLISHED      => 'Publicado',
                        Texture::STATUS_REJECTED       => 'Rejeitado',
                        default                        => ucfirst($state),
                    })
                    ->color(fn(string $state) => match ($state) {
                        Texture::STATUS_PENDING_REVIEW => 'warning',
                        Texture::STATUS_PUBLISHED      => 'success',
                        Texture::STATUS_REJECTED       => 'danger',
                        default                        => 'gray',
                    }),

                IconColumn::make('is_free')
                    ->label('Gratuita')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Enviado por')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        Texture::STATUS_DRAFT          => 'Rascunho',
                        Texture::STATUS_PENDING_REVIEW => 'Aguardando revisão',
                        Texture::STATUS_PUBLISHED      => 'Publicado',
                        Texture::STATUS_REJECTED       => 'Rejeitado',
                    ]),

                SelectFilter::make('texture_category_id')
                    ->label('Categoria')
                    ->options(fn() => TextureCategory::query()->orderBy('name')->pluck('name', 'id')),

                SelectFilter::make('material_type')
                    ->label('Tipo')
                    ->options(Texture::MATERIAL_TYPES),

                TernaryFilter::make('is_free')->label('Gratuita'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('approve')
                        ->label('Aprovar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->authorize('approve')
                        ->visible(fn(Texture $record) => $record->isPending() || $record->isRejected())
                        ->requiresConfirmation()
                        ->action(function (Texture $record, Action $action) {
                            if ($blocker = $record->publishBlocker()) {
                                Notification::make()->title('Não publicada')->body($blocker)->warning()->send();
                                $action->halt();
                            }

                            $record->update([
                                'status'           => Texture::STATUS_PUBLISHED,
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
                        ->visible(fn(Texture $record) => ! $record->isRejected())
                        ->schema([
                            Textarea::make('rejection_reason')
                                ->label('Motivo da rejeição')
                                ->required(),
                        ])
                        ->action(function (Texture $record, array $data) {
                            $record->update([
                                'status'           => Texture::STATUS_REJECTED,
                                'approved_by'      => auth()->id(),
                                'approved_at'      => now(),
                                'rejection_reason' => $data['rejection_reason'],
                            ]);
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
