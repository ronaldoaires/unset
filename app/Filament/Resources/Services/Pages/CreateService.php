<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Services\ImageService;
use App\Support\SlugGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = SlugGenerator::unique(Service::class, $data['name']);

        $cover = $data['cover_path'] ?? null;

        // Only a fresh upload is stored; anything else means no cover.
        $data['cover_path'] = $cover instanceof UploadedFile
            ? app(ImageService::class)->storeWithSlug(
                slug: $data['slug'],
                file: $cover,
                directory: 'services',
                disk: 'public',
            )
            : null;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Serviço ' . $this->record->name . ' criado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
