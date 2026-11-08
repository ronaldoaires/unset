<?php

namespace App\Filament\Resources\TextureCategories;

use App\Filament\Resources\TextureCategories\Pages\ManageTextureCategories;
use App\Filament\Resources\TextureCategories\Schemas\TextureCategoryForm;
use App\Filament\Resources\TextureCategories\Tables\TextureCategoriesTable;
use App\Models\TextureCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class TextureCategoryResource extends Resource
{
    protected static ?string $model = TextureCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Categorias de textura';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?string $modelLabel = 'Categoria de textura';

    protected static ?string $pluralModelLabel = 'Categorias de textura';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return TextureCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TextureCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTextureCategories::route('/'),
        ];
    }
}
