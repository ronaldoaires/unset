<?php

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Models\Brand;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ToggleColumn;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_thumb_url')
                    ->label('')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('País')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('website')
                    ->label('Site')
                    ->url(fn(?string $state) => $state)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->visibleFrom('md'),   // hidden on phones, shown from the md breakpoint up

                TextColumn::make('families_count')
                    ->label('Famílias')
                    ->counts('families')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Ativa')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    ->disabled(fn(Brand $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (Brand $record, bool $state) {
                        Notification::make()
                            ->title('Marca ' . $record->name . ($state ? ' ativada' : ' desativada'))
                            ->success()
                            ->send();
                    })->tooltip(fn(Brand $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' a marca'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Ativa'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Brand $record) => 'Editar ' . $record->name)
                    ->successNotificationTitle(fn(Brand $record) => 'Marca ' . $record->name . ' atualizada')
                    ->mutateDataUsing(function (array $data, Brand $record): array {
                        $images  = app(ImageService::class);
                        $oldPath = $record->logo_path;
                        $logo    = $data['logo_path'] ?? null;

                        // New upload replaces the old files; empty means the logo was
                        // removed; a string means the stored path is unchanged.
                        if ($logo instanceof UploadedFile) {
                            $data['logo_path'] = $images->storeWithSlug(
                                slug: $record->slug,
                                file: $logo,
                                directory: 'brands',
                                disk: 'public',
                                oldPath: $oldPath,
                            );
                        } elseif (blank($logo)) {
                            if ($oldPath) {
                                $images->deleteImageAndThumb($oldPath, 'public');
                            }

                            $data['logo_path'] = null;
                        } else {
                            $data['logo_path'] = $oldPath;
                        }

                        return $data;
                    }),
                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Brand $record) => 'Excluir ' . $record->name)
                    ->successNotificationTitle(fn(Brand $record) => 'Marca ' . $record->name . ' excluída'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
