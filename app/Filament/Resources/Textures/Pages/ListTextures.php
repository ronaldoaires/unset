<?php

namespace App\Filament\Resources\Textures\Pages;

use App\Filament\Resources\Textures\TextureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTextures extends ListRecords
{
    protected static string $resource = TextureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
