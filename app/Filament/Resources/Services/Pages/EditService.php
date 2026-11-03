<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Services\ImageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // The projects FK is restrictive: move or delete the portfolio first.
                ->hidden(fn (Service $record) => $record->projects()->exists())
                ->successNotificationTitle(fn (Service $record) => 'Serviço ' . $record->name . ' excluído'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $images  = app(ImageService::class);
        $oldPath = $this->record->cover_path;
        $cover   = $data['cover_path'] ?? null;

        // New upload replaces the old files; empty means the cover was
        // removed; a string means the stored path is unchanged.
        if ($cover instanceof UploadedFile) {
            $data['cover_path'] = $images->storeWithSlug(
                slug: $this->record->slug,
                file: $cover,
                directory: 'services',
                disk: 'public',
                oldPath: $oldPath,
            );
        } elseif (blank($cover)) {
            if ($oldPath) {
                $images->deleteImageAndThumb($oldPath, 'public');
            }

            $data['cover_path'] = null;
        } else {
            $data['cover_path'] = $oldPath;
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Serviço ' . $this->record->name . ' atualizado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
