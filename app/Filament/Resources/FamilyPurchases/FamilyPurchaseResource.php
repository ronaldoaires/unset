<?php

namespace App\Filament\Resources\FamilyPurchases;

use App\Filament\Resources\FamilyPurchases\Pages\ManageFamilyPurchases;
use App\Filament\Resources\FamilyPurchases\Tables\FamilyPurchasesTable;
use App\Models\FamilyPurchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class FamilyPurchaseResource extends Resource
{
    protected static ?string $model = FamilyPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Compras de famílias';

    protected static string|UnitEnum|null $navigationGroup = 'Assinaturas';

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return FamilyPurchasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFamilyPurchases::route('/'),
        ];
    }
}