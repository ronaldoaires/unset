<?php

namespace App\Filament\Resources\TextureCategories\Pages;

use App\Filament\Resources\TextureCategories\TextureCategoryResource;
use App\Models\TextureCategory;
use App\Support\SlugGenerator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTextureCategories extends ManageRecords
{
    protected static string $resource = TextureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // The slug is generated once and kept stable on edit.
                ->mutateDataUsing(function (array $data): array {
                    $data['slug'] = SlugGenerator::unique(TextureCategory::class, $data['name']);

                    return $data;
                })
                ->successNotificationTitle(fn(TextureCategory $record) => 'Categoria ' . $record->name . ' criada'),
        ];
    }
}
