<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Support\SlugGenerator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['slug'] = SlugGenerator::unique(Category::class, $data['name']);

                    return $data;
                })
                ->successNotificationTitle(fn (Category $record) => 'Categoria ' . $record->name . ' criada'),
        ];
    }
}