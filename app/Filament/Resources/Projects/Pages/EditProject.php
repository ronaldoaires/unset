<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ImageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotificationTitle(fn (Project $record) => 'Projeto ' . $record->title . ' excluído'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $cover = $data['cover_path'] ?? null;

        // A string means the stored cover is unchanged (the field is required,
        // so it can't be emptied); only a new upload replaces the files.
        $data['cover_path'] = $cover instanceof UploadedFile
            ? app(ImageService::class)->storeWithSlug(
                slug: $this->record->slug,
                file: $cover,
                directory: 'projects',
                disk: 'public',
                oldPath: $this->record->cover_path,
            )
            : $this->record->cover_path;

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Projeto ' . $this->record->title . ' atualizado';
    }
}
