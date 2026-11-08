<?php

namespace App\Filament\Resources\Textures\Pages;

use App\Filament\Resources\Textures\TextureResource;
use App\Models\Texture;
use App\Services\TextureMapService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;

class EditTexture extends EditRecord
{
    protected static string $resource = TextureResource::class;

    /** Captured before cover_upload is stripped, consumed in afterSave(). */
    protected UploadedFile|string|null $incomingCover = null;

    protected bool $coverTouched = false;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Same approval stamping as EditFamily.
        if (auth()->user()->isSuperAdmin() && blank($this->record->approved_at)) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        if (! auth()->user()->isSuperAdmin() && $this->record->isRejected()) {
            $data['approved_by']      = null;
            $data['approved_at']      = null;
            $data['rejection_reason'] = null;
        }

        $this->coverTouched  = array_key_exists('cover_upload', $data);
        $this->incomingCover = $data['cover_upload'] ?? null;

        unset($data['maps_upload'], $data['cover_upload']);

        // Never publish an image texture that has no diffuse map.
        if (($data['status'] ?? null) === Texture::STATUS_PUBLISHED) {
            $probe = $this->record->replicate()->fill($data);
            $probe->id = $this->record->id;

            if ($blocker = $probe->publishBlocker()) {
                $data['status'] = $this->record->isPublished() ? Texture::STATUS_DRAFT : $this->record->status;

                Notification::make()
                    ->title('Não publicada')
                    ->body($blocker)
                    ->warning()
                    ->send();
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->coverTouched) {
            return;
        }

        $textures = app(TextureMapService::class);

        // A string is the stored path (unchanged); empty means the admin
        // removed the cover; an UploadedFile is a new cover.
        if ($this->incomingCover instanceof UploadedFile) {
            $textures->storeCover($this->record, $this->incomingCover);
        } elseif (blank($this->incomingCover)) {
            $textures->storeCover($this->record, null);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
