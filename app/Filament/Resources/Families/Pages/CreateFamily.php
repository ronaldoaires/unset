<?php

namespace App\Filament\Resources\Families\Pages;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\Family;
use App\Models\FamilyFile;
use App\Services\ImageService;
use App\Support\SlugGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\RfaFileService;

class CreateFamily extends CreateRecord
{
    protected static string $resource = FamilyResource::class;

    /**
     * Ownership is always the creator — a moderator can never submit a
     * family "as" someone else, that's what keeps the update()/delete()
     * ownership check in FamilyPolicy meaningful. The slug is generated
     * here too since the field isn't exposed in the form.
     *
     * rfa_upload / cover_image_upload aren't Family columns — they're
     * handled separately in afterCreate() — so they're stripped here
     * before the record itself is created.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['slug']    = SlugGenerator::unique(Family::class, $data['name']);

        unset($data['rfa_upload'], $data['cover_image_upload'], $data['tag_list']);

        if (auth()->user()->isSuperAdmin()) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $formData = $this->form->getState();

        $tags = $formData['tag_list'] ?? [];
        $this->record->syncTags($tags);

        if (! empty($formData['rfa_upload'])) {
            $this->storeRfaFile($formData['rfa_upload']);
        }

        if (! empty($formData['cover_image_upload'])) {
            $this->storeCoverImage($formData['cover_image_upload']);
        }
    }

    /**
     * rfa_upload uses Filament's default auto-storage, so $path starts as the
     * randomly named file; it is renamed to "{slug}.rfa" before being recorded.
     */
    protected function storeRfaFile(string $path): void
    {
        // RFA lives on the private disk (images stay on "public").
        $disk = Storage::disk('local');

        $path = app(RfaFileService::class)->renameToSlug($path, $this->record->slug);

        $this->record->files()->create([
            'type'          => FamilyFile::TYPE_RFA,
            'disk'          => 'local',
            'path'          => $path,
            'original_name' => basename($path),
            'size_bytes'    => $disk->size($path),
            'mime_type'     => $disk->mimeType($path),
            'is_primary'    => true,
        ]);
    }

    /**
     * cover_image_upload is kept as a raw UploadedFile (storeFiles(false)
     * on the form field) so ImageService can convert it to webp and
     * generate the thumbnail, exactly like the hotel project does.
     */
    protected function storeCoverImage(UploadedFile $file): void
    {
        $disk  = Storage::disk('public');
        $image = app(ImageService::class);

        $imagePath = $image->storeWithSlug(
            slug: $this->record->slug,
            file: $file,
            directory: 'families/images',
            disk: 'public',
        );

        $thumbPath = dirname($imagePath) . '/thumbs/' . basename($imagePath);

        $this->record->files()->create([
            'type'          => FamilyFile::TYPE_IMAGE,
            'disk'          => 'public',
            'path'          => $imagePath,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes'    => $disk->size($imagePath),
            'mime_type'     => 'image/webp',
            'is_primary'    => true,
        ]);

        $this->record->files()->create([
            'type'          => FamilyFile::TYPE_THUMB,
            'disk'          => 'public',
            'path'          => $thumbPath,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes'    => $disk->size($thumbPath),
            'mime_type'     => 'image/webp',
            'is_primary'    => true,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
