<?php

namespace App\Filament\Resources\Textures;

use App\Filament\Resources\Textures\Pages\CreateTexture;
use App\Filament\Resources\Textures\Pages\EditTexture;
use App\Filament\Resources\Textures\Pages\ListTextures;
use App\Filament\Resources\Textures\RelationManagers\TextureMapsRelationManager;
use App\Filament\Resources\Textures\Schemas\TextureForm;
use App\Filament\Resources\Textures\Tables\TexturesTable;
use App\Models\Texture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TextureResource extends Resource
{
    protected static ?string $model = Texture::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Texturas';

    protected static ?string $modelLabel = 'Textura';

    protected static ?string $pluralModelLabel = 'Texturas';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TextureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TexturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TextureMapsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTextures::route('/'),
            'create' => CreateTexture::route('/create'),
            'edit'   => EditTexture::route('/{record}/edit'),
        ];
    }

    /**
     * Same lock as families: once a superadmin has approved or edited a
     * submission, only the superadmin may keep changing it.
     */
    public static function isLocked(?Texture $record): bool
    {
        if ($record === null) {
            return false;
        }

        return filled($record->approved_at)
            && ! $record->isRejected()
            && ! auth()->user()->isSuperAdmin();
    }
}
