<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use App\Services\ImageService;
use App\Support\SlugGenerator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Http\UploadedFile;

class ManageBrands extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['slug'] = SlugGenerator::unique(Brand::class, $data['name']);

                    $logo = $data['logo_path'] ?? null;

                    // Only a fresh upload is stored; anything else means no logo.
                    $data['logo_path'] = $logo instanceof UploadedFile
                        ? app(ImageService::class)->storeWithSlug(
                            slug: $data['slug'],
                            file: $logo,
                            directory: 'brands',
                            disk: 'public',
                        )
                        : null;

                    return $data;
                })->successNotificationTitle(fn (Brand $record) => 'Marca ' . $record->name . ' criada'),
        ];
    }
}