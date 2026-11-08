<?php

namespace App\Filament\Resources\Textures\Pages;

use App\Filament\Resources\Textures\TextureResource;
use App\Models\Texture;
use App\Services\TextureMapService;
use App\Support\SlugGenerator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;

class CreateTexture extends CreateRecord
{
    protected static string $resource = TextureResource::class;

    /**
     * Captured in mutateFormDataBeforeCreate(): once the record exists the
     * "Mapas" section (create-only) is hidden, so getState() in afterCreate()
     * would no longer return its files.
     *
     * @var array<UploadedFile>
     */
    protected array $incomingMaps = [];

    protected ?UploadedFile $incomingCover = null;

    /**
     * maps_upload / cover_upload are not columns: they are handled in
     * afterCreate(), once the record (and its slug) exists.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['slug']    = SlugGenerator::unique(Texture::class, $data['name']);

        $this->incomingMaps  = array_values(array_filter(
            (array) ($data['maps_upload'] ?? []),
            fn($file) => $file instanceof UploadedFile,
        ));
        $this->incomingCover = ($data['cover_upload'] ?? null) instanceof UploadedFile ? $data['cover_upload'] : null;

        unset($data['maps_upload'], $data['cover_upload']);

        if (auth()->user()->isSuperAdmin()) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $textures = app(TextureMapService::class);

        if ($this->incomingCover) {
            $textures->storeCover($this->record, $this->incomingCover);
        }

        if ($this->incomingMaps) {
            $result = $textures->storeMany($this->record, $this->incomingMaps);
            $this->notifySkipped($result['skipped']);
        }

        // A published image texture without a diffuse map goes back to draft.
        if ($this->record->isPublished() && $this->record->publishBlocker()) {
            $this->record->update(['status' => Texture::STATUS_DRAFT]);

            Notification::make()
                ->title('Textura salva como rascunho')
                ->body($this->record->publishBlocker())
                ->warning()
                ->send();
        }
    }

    private function notifySkipped(array $skipped): void
    {
        if (! $skipped) {
            return;
        }

        Notification::make()
            ->title('Alguns arquivos não foram reconhecidos')
            ->body('Não deu para saber o tipo de mapa pelo nome: ' . implode(', ', $skipped)
                . '. Renomeie (ex.: diffuse.png, normal.png, roughness.png) ou envie pela aba Mapas escolhendo o tipo.')
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
