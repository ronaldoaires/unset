<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ImageService;
use App\Support\SlugGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = SlugGenerator::unique(Project::class, $data['title']);

        $cover = $data['cover_path'] ?? null;

        // The cover is required by the form, so this is always a fresh upload.
        if ($cover instanceof UploadedFile) {
            $data['cover_path'] = app(ImageService::class)->storeWithSlug(
                slug: $data['slug'],
                file: $cover,
                directory: 'projects',
                disk: 'public',
            );
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Projeto ' . $this->record->title . ' criado. Agora adicione as fotos da galeria.';
    }

    /**
     * Unlike the other resources, go to the edit page: the gallery lives
     * there and is the natural next step after creating a project.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
