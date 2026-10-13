<?php

namespace App\Filament\Resources\Families;

use App\Filament\Resources\Families\Pages\CreateFamily;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Resources\Families\Schemas\FamilyForm;
use App\Filament\Resources\Families\Tables\FamiliesTable;
use App\Models\Family;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Famílias';

    protected static ?string $modelLabel = 'Família';

    protected static ?string $pluralModelLabel = 'Famílias';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return FamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFamilies::route('/'),
            'create' => CreateFamily::route('/create'),
            'edit'   => EditFamily::route('/{record}/edit'),
        ];
    }

    /**
     * A family is "locked" once a superadmin has touched it (approved_at is
     * set): from that point on, only the superadmin may keep editing it —
     * the moderator who created it can no longer change or delete it.
     * Used by the form (to disable fields) and by the files relation
     * manager (to disable file uploads/deletion) alike.
     */
    public static function isLocked(?Family $record): bool
    {
        if ($record === null) {
            return false;
        }

        return filled($record->approved_at)
            && ! $record->isRejected()
            && ! auth()->user()->isSuperAdmin();
    }
}
