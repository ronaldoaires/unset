<?php

namespace App\Filament\Resources\Families\Pages;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\FamilyFile;
use App\Services\ImageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\RfaFileService;
use App\Models\Family;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    /**
     * Captured in mutateFormDataBeforeSave() (before rfa_upload /
     * cover_image_upload are stripped from $data) and consumed in
     * afterSave(), once $this->record is guaranteed to be saved.
     */
    protected ?string $incomingRfaPath = null;

    protected UploadedFile|string|null $incomingCoverImage = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->failureNotificationTitle(
                    fn(Family $record) => 'Não foi possível excluir ' . $record->name . ': a família possui compras registradas'
                ),
        ];
    }

    /**
     * The moment a superadmin saves an edit on a submission nobody has
     * reviewed yet, it locks: from then on only the superadmin can touch
     * it (see FamilyResource::isLocked() and FamilyPolicy::update()).
     * If it's already approved/locked, we leave the original approver
     * and timestamp untouched.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()->isSuperAdmin() && blank($this->record->approved_at)) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $this->incomingRfaPath    = $data['rfa_upload'] ?? null;
        $this->incomingCoverImage = $data['cover_image_upload'] ?? null;

        unset($data['rfa_upload'], $data['cover_image_upload'], $data['tag_list']);

        return $data;
    }

    protected function afterSave(): void
    {
        $tags = $this->form->getState()['tag_list'] ?? [];
        $this->record->syncTags($tags);

        $this->syncRfaFile($this->incomingRfaPath);
        $this->syncCoverImage($this->incomingCoverImage);
    }

    /**
     * If the field still holds the same path the record already had,
     * nothing changed and there's nothing to do. A different path means
     * the admin replaced the file, so the old one is swapped out.
     */
    protected function syncRfaFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $existing = $this->record->rfaFiles()->first();

        if ($existing && $existing->path === $path) {
            return;
        }

        // The old file is removed first so its "{slug}.rfa" name is free
        // for the replacement.
        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->path);
            $existing->delete();
        }

        $disk = Storage::disk('public');

        $path = app(RfaFileService::class)->renameToSlug($path, $this->record->slug);

        $this->record->files()->create([
            'type'          => FamilyFile::TYPE_RFA,
            'disk'          => 'public',
            'path'          => $path,
            'original_name' => basename($path),
            'size_bytes'    => $disk->size($path),
            'mime_type'     => $disk->mimeType($path),
            'is_primary'    => true,
        ]);
    }

    /**
     * A string here means the field still holds the existing stored
     * path (nothing to do). Only a real UploadedFile means the admin
     * picked a new cover image, which needs to go through ImageService
     * again for the webp + thumbnail conversion.
     */
    protected function syncCoverImage(UploadedFile|string|null $file): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $disk  = Storage::disk('public');
        $image = app(ImageService::class);

        $oldImage = $this->record->primaryImage;
        $oldThumb = $this->record->thumbnail;

        $imagePath = $image->storeWithSlug(
            slug: $this->record->slug,
            file: $file,
            directory: 'families/images',
            disk: 'public',
            oldPath: $oldImage?->path,
        );

        $thumbPath = dirname($imagePath) . '/thumbs/' . basename($imagePath);

        $attributes = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => 'image/webp',
        ];

        if ($oldImage) {
            $oldImage->update([...$attributes, 'path' => $imagePath, 'size_bytes' => $disk->size($imagePath)]);
        } else {
            $this->record->files()->create([
                ...$attributes,
                'type'       => FamilyFile::TYPE_IMAGE,
                'disk'       => 'public',
                'path'       => $imagePath,
                'size_bytes' => $disk->size($imagePath),
                'is_primary' => true,
            ]);
        }

        if ($oldThumb) {
            $oldThumb->update([...$attributes, 'path' => $thumbPath, 'size_bytes' => $disk->size($thumbPath)]);
        } else {
            $this->record->files()->create([
                ...$attributes,
                'type'       => FamilyFile::TYPE_THUMB,
                'disk'       => 'public',
                'path'       => $thumbPath,
                'size_bytes' => $disk->size($thumbPath),
                'is_primary' => true,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
